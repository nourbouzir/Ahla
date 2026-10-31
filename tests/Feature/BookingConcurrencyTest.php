<?php

namespace Tests\Feature;

use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\PaymentGateway;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Experience\Models\Experience;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Payment\Gateways\SandboxPaymentGateway;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Verification\Services\VerifiedHostEligibility;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Proves there is no double booking when several hosts' accept clicks happen at
 * the same instant. It starts real parallel PHP processes against MySQL, so it
 * only runs on request (SQLite cannot lock rows):
 *
 *   AHLA_MYSQL_TESTS=1 DB_CONNECTION=mysql DB_DATABASE=ahla_test DB_USERNAME=ahla DB_PASSWORD=... php artisan test --filter=BookingConcurrency
 *
 * The database name must end in "_test" because the test wipes it.
 */
class BookingConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        if (! getenv('AHLA_MYSQL_TESTS') || getenv('DB_CONNECTION') !== 'mysql' || ! str_ends_with((string) getenv('DB_DATABASE'), '_test')) {
            $this->markTestSkipped('Set AHLA_MYSQL_TESTS=1 with DB_CONNECTION=mysql and a *_test database to run.');
        }

        parent::setUp();
    }

    /** @return list<array{int, string}> [exit code, output] of each finished process */
    private function runInParallel(array $bookingIds): array
    {
        // Children run the real app config: drop the fakes PHPUnit injects, share the sandbox gateway via a file cache.
        $env = array_filter(getenv(), fn ($k) => ! str_starts_with($k, 'AHLA_'), ARRAY_FILTER_USE_KEY);
        $env = array_merge($env, ['APP_ENV' => 'testing', 'CACHE_STORE' => 'file']);
        $procs = [];

        foreach ($bookingIds as $i => $id) {
            $procs[$i] = proc_open(
                [PHP_BINARY, base_path('artisan'), 'booking:accept', (string) $id],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                base_path(),
                $env,
            );
            $procs[$i] = [$procs[$i], $pipes];
        }

        $results = [];
        foreach ($procs as $i => [$proc, $pipes]) {
            $out = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $results[$i] = [proc_close($proc), trim($out)];
        }

        return $results;
    }

    private function hostWithRequests(int $count, array $slots): array
    {
        config(['cache.default' => 'file']);
        Cache::flush();
        $this->app->singleton(PaymentGateway::class, SandboxPaymentGateway::class);
        $this->app->singleton(HostEligibility::class, VerifiedHostEligibility::class);
        $this->app->singleton(AvailabilityChecker::class, WeeklyAvailabilityChecker::class);

        $host = User::factory()->verifiedHost()->create();
        $experience = Experience::factory()->published()->create(['host_profile_id' => $host->hostProfile->id, 'duration_hours' => 2]);
        $date = now()->addDays(4)->toDateString();
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $booking = Booking::factory()->create([
                'experience_id' => $experience->id, 'host_id' => $host->id, 'date' => $date,
                'start_time' => $slots[$i][0], 'end_time' => $slots[$i][1], 'displayed_price' => '50.000',
            ]);
            app(PaymentService::class)->authorise($booking);
            $ids[] = $booking->id;
        }

        return [$host, $ids, $date];
    }

    public function test_overlapping_requests_accepted_at_once_never_both_confirm(): void
    {
        // Six overlapping requests, all accepted in parallel.
        [$host, $ids, $date] = $this->hostWithRequests(6, array_fill(0, 6, ['10:00:00', '12:00:00']));

        $results = $this->runInParallel($ids);

        $accepted = collect($results)->filter(fn ($r) => $r[1] === 'ACCEPTED')->count();
        $this->assertSame(1, $accepted, 'exactly one overlapping request may win: '.json_encode($results));
        $this->assertSame(1, Booking::where('host_id', $host->id)->where('status', BookingStatus::Confirmed)->count());
    }

    public function test_at_most_two_confirmed_per_day_under_parallel_accepts(): void
    {
        // Five non-overlapping requests on one day, all accepted in parallel.
        [$host, $ids] = $this->hostWithRequests(5, [
            ['08:00:00', '09:00:00'], ['10:00:00', '11:00:00'], ['12:00:00', '13:00:00'], ['14:00:00', '15:00:00'], ['16:00:00', '17:00:00'],
        ]);

        $results = $this->runInParallel($ids);

        $this->assertSame(2, collect($results)->filter(fn ($r) => $r[1] === 'ACCEPTED')->count(), json_encode($results));
        $this->assertSame(2, Booking::where('host_id', $host->id)->where('status', BookingStatus::Confirmed)->count());
        $this->assertSame(3, collect($results)->filter(fn ($r) => str_contains($r[1], 'REFUSED:daily_limit'))->count(), json_encode($results));
    }
}
