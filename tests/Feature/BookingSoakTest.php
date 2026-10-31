<?php

namespace Tests\Feature;

use App\Contracts\Payments\PaymentGateway;
use App\Jobs\ReconcilePayments;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Experience\Models\Availability;
use App\Modules\Experience\Models\Experience;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Gateways\SandboxPaymentGateway;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Soak test (issue #39): many tourists hammer the same hosts at once, hosts accept
 * in parallel, and the payment gateway misbehaves on purpose (timeouts, declines
 * and lost answers). Afterwards the books must still balance. MySQL only:
 *
 *   AHLA_MYSQL_TESTS=1 AHLA_SOAK_PROCESSES=24 AHLA_SANDBOX_FAULT_RATE=0.15 DB_CONNECTION=mysql DB_DATABASE=ahla_test ... php artisan test --filter=BookingSoak
 */
class BookingSoakTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        if (! getenv('AHLA_MYSQL_TESTS') || getenv('DB_CONNECTION') !== 'mysql' || ! str_ends_with((string) getenv('DB_DATABASE'), '_test')) {
            $this->markTestSkipped('Set AHLA_MYSQL_TESTS=1 with DB_CONNECTION=mysql and a *_test database to run.');
        }

        parent::setUp();
    }

    /** @param list<list<string>> $commands artisan arguments, one process each, all started together */
    private function runAll(array $commands, float $faultRate): array
    {
        $env = array_filter(getenv(), fn ($k) => ! str_starts_with($k, 'AHLA_'), ARRAY_FILTER_USE_KEY);
        $env = array_merge($env, ['APP_ENV' => 'testing', 'CACHE_STORE' => 'file', 'AHLA_SANDBOX_FAULT_RATE' => (string) $faultRate]);
        $procs = [];

        foreach ($commands as $i => $args) {
            $procs[$i] = proc_open([PHP_BINARY, base_path('artisan'), ...$args], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $env);
            $procs[$i] = [$procs[$i], $pipes];
        }

        $out = [];
        foreach ($procs as $i => [$proc, $pipes]) {
            $out[$i] = trim(stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        }

        return $out;
    }

    public function test_the_books_balance_after_a_parallel_storm_with_gateway_faults(): void
    {
        config(['cache.default' => 'file']);
        Cache::flush();
        $this->app->singleton(PaymentGateway::class, SandboxPaymentGateway::class);

        $processes = (int) (getenv('AHLA_SOAK_PROCESSES') ?: 16);
        $faultRate = (float) (getenv('AHLA_SANDBOX_FAULT_RATE') ?: 0.15);
        $date = now()->addDays(4)->toDateString();

        // Three hosts; every tourist wants the same few slots.
        $experiences = collect(range(1, 3))->map(function () {
            $host = User::factory()->verifiedHost()->create();
            foreach (range(0, 6) as $day) {
                Availability::factory()->create(['host_profile_id' => $host->hostProfile->id, 'day_of_week' => $day, 'starts_at' => '08:00:00', 'ends_at' => '22:00:00']);
            }

            return Experience::factory()->published()->create(['host_profile_id' => $host->hostProfile->id, 'duration_hours' => 2, 'price_per_person' => '40.000', 'max_group_size' => 4]);
        });
        $tourists = User::factory()->tourist()->count($processes)->create();
        $slots = ['09:00', '10:00', '11:00', '15:00', '16:00'];

        // 1. Everybody requests at once (slots overlap, nothing is confirmed yet so all are allowed).
        $requests = $this->runAll($tourists->map(fn ($t, $i) => ['booking:request', $t->id, $experiences[$i % 3]->id, $date, $slots[$i % 5], '2'])->all(), $faultRate);

        // 2. Hosts accept everything in parallel, and each booking is accepted twice (double clicks).
        $ids = Booking::pluck('id');
        $accepts = $this->runAll($ids->flatMap(fn ($id) => [['booking:accept', $id], ['booking:accept', $id]])->all(), $faultRate);

        // 3. The reconcile job heals anything the faults left half-done, as in production.
        config(['ahla.sandbox.fault_rate' => 0]);
        (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class), app(BookingService::class));
        Payment::query()->update(['updated_at' => now()->subMinutes(5)]);
        (new ReconcilePayments)->handle(app(RefundService::class), app(PaymentService::class), app(BookingService::class));

        $report = json_encode(['requests' => array_count_values($requests), 'accepts' => array_count_values($accepts)]);
        if (getenv('AHLA_SOAK_VERBOSE')) {
            fwrite(STDERR, $report.'
'.json_encode(Booking::pluck('status')->map->value->countBy())).'
';
        }
        $this->assertGreaterThan(0, Booking::count(), 'some requests must get through: '.$report);

        // INVARIANTS ------------------------------------------------------------------
        $confirmed = Booking::where('status', BookingStatus::Confirmed)->get();

        // Never more than 2 confirmed per host per day, and no two overlapping.
        foreach ($confirmed->groupBy(fn ($b) => $b->host_id.'|'.$b->date->toDateString()) as $key => $group) {
            $this->assertLessThanOrEqual(2, $group->count(), "daily limit broken for $key. $report");
            foreach ($group as $a) {
                foreach ($group as $b) {
                    if ($a->id < $b->id) {
                        $this->assertFalse($a->start_time < $b->end_time && $a->end_time > $b->start_time, "overlap between #{$a->id} and #{$b->id}. $report");
                    }
                }
            }
        }

        // Money and status always agree.
        foreach (Booking::with('payment')->get() as $booking) {
            $payment = $booking->payment;
            $this->assertNotNull($payment, "booking #{$booking->id} without a payment");

            match ($booking->status) {
                BookingStatus::Confirmed => $this->assertSame(PaymentStatus::Captured, $payment->status, "confirmed #{$booking->id} must be paid"),
                BookingStatus::Requested => $this->assertContains($payment->status, [PaymentStatus::Authorised], "requested #{$booking->id} must only be reserved"),
                BookingStatus::Declined, BookingStatus::Expired => $this->assertContains($payment->status, [PaymentStatus::Released, PaymentStatus::Refunded], "closed #{$booking->id} must hold no money"),
                default => null,
            };
        }

        $this->assertSame(0, Payment::where('status', PaymentStatus::Captured)->whereHas('booking', fn ($q) => $q->where('status', '!=', BookingStatus::Confirmed))->count(), 'captured money for an unconfirmed booking');
    }
}
