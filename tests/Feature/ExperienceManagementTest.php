<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Experience\Models\Experience;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExperienceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'title' => 'Medina walk', 'category' => 'Culture', 'description' => 'A slow walk.', 'duration_hours' => 3,
            'price_per_person' => '45.5', 'max_group_size' => 4, 'meeting_point' => 'Bab Bhar', 'interests' => ['history'],
            'published' => '1',
        ];
    }

    public function test_host_creates_edits_and_unpublishes_an_experience(): void
    {
        Storage::fake('public');
        $host = User::factory()->verifiedHost()->create();

        $this->actingAs($host)->post('/host/experiences', $this->payload(['photo' => UploadedFile::fake()->image('p.jpg')]))
            ->assertRedirect('/host/experiences');

        $experience = Experience::firstOrFail();
        $this->assertSame('45.500', (string) $experience->price_per_person);
        $this->assertSame(['history'], $experience->interests);
        $this->assertTrue($experience->published);
        $this->assertStringContainsString('experiences/', $experience->photo_url);

        $this->put("/host/experiences/{$experience->id}", $this->payload(['title' => 'Renamed']))->assertRedirect();
        $this->assertSame('Renamed', $experience->fresh()->title);

        $this->post("/host/experiences/{$experience->id}/toggle");
        $this->assertFalse($experience->fresh()->published);
        $this->get('/host/experiences')->assertOk()->assertSee('Renamed');
    }

    public function test_limits_from_the_srs_are_enforced(): void
    {
        $host = User::factory()->verifiedHost()->create();

        foreach ([
            ['duration_hours' => 0], ['duration_hours' => 7], ['max_group_size' => 7], ['max_group_size' => 0],
            ['price_per_person' => '0'], ['price_per_person' => '12.3456'], ['interests' => ['skydiving']],
        ] as $bad) {
            $this->actingAs($host)->post('/host/experiences', $this->payload($bad))->assertSessionHasErrors();
        }

        $this->assertSame(0, Experience::count());
    }

    public function test_a_host_cannot_touch_someone_elses_experience(): void
    {
        $mine = User::factory()->verifiedHost()->create();
        $other = Experience::factory()->create();

        $this->actingAs($mine)->get("/host/experiences/{$other->id}/edit")->assertNotFound();
        $this->put("/host/experiences/{$other->id}", $this->payload())->assertNotFound();
        $this->post("/host/experiences/{$other->id}/toggle")->assertNotFound();
    }

    public function test_tourists_cannot_manage_experiences(): void
    {
        $this->actingAs(User::factory()->tourist()->create())->get('/host/experiences')->assertForbidden();
    }

    public function test_weekly_slots_blocked_dates_and_the_checker(): void
    {
        $host = User::factory()->verifiedHost()->create();
        $monday = now()->next('Monday')->toDateString();

        $this->actingAs($host)->post('/host/availability/slots', ['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '17:00'])
            ->assertSessionHasNoErrors();
        $this->post('/host/availability/slots', ['day_of_week' => 1, 'starts_at' => '16:00', 'ends_at' => '18:00'])->assertSessionHasErrors('starts_at');
        $this->post('/host/availability/slots', ['day_of_week' => 1, 'starts_at' => '10:00', 'ends_at' => '09:00'])->assertSessionHasErrors('ends_at');

        $checker = new WeeklyAvailabilityChecker;
        $this->assertTrue($checker->isSlotAvailable($host->id, $monday, '09:00', '12:00'));
        $this->assertTrue($checker->isSlotAvailable($host->id, $monday, '14:00:00', '17:00:00'));
        $this->assertFalse($checker->isSlotAvailable($host->id, $monday, '16:00', '18:00'), 'ends after the hours');
        $this->assertFalse($checker->isSlotAvailable($host->id, $monday, '08:00', '10:00'), 'starts before the hours');
        $this->assertFalse($checker->isSlotAvailable($host->id, now()->next('Tuesday')->toDateString(), '09:00', '12:00'), 'wrong day');
        $this->assertFalse($checker->isSlotAvailable($host->id, $monday, '12:00', '12:00'), 'empty interval');

        $this->post('/host/availability/blocked', ['date' => $monday, 'reason' => 'Holiday']);
        $this->assertFalse($checker->isSlotAvailable($host->id, $monday, '09:00', '12:00'), 'blocked date');

        $this->assertFalse($checker->isSlotAvailable(User::factory()->tourist()->create()->id, $monday, '09:00', '12:00'));
        $this->get('/host/availability')->assertOk()->assertSee('Holiday');
    }

    public function test_blocking_a_past_date_is_refused(): void
    {
        $host = User::factory()->verifiedHost()->create();

        $this->actingAs($host)->post('/host/availability/blocked', ['date' => now()->subDay()->toDateString()])->assertSessionHasErrors('date');
    }
}
