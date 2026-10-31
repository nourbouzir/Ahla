<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Matching\Services\MatchingService;
use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Enums\HostStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesHosts;
use Tests\TestCase;

class SearchAndMatchingTest extends TestCase
{
    use MakesHosts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function names(array $query): array
    {
        return collect($this->getJson('/api/search?'.http_build_query($query))->assertOk()->json('data'))->pluck('name')->all();
    }

    public function test_unverified_hosts_and_unpublished_experiences_never_appear(): void
    {
        $this->makeHost('Visible');
        $this->makeHost('Pending', status: HostStatus::Pending);
        $this->makeHost('Rejected', status: HostStatus::Rejected);
        $this->makeHost('Suspended', status: HostStatus::Suspended);
        $this->makeHost('Draft', published: false);

        $this->assertSame(['Visible'], $this->names([]));
    }

    public function test_each_filter_works_alone_and_combined(): void
    {
        $this->makeHost('Amel', 'Tunis', ['ar', 'fr'], ['food', 'history'], 4.9, price: '40.000');
        $this->makeHost('Ben', 'Sousse', ['en'], ['art'], 3.5, price: '90.000');
        $this->makeHost('Cyrine', 'Tunis', ['en', 'fr'], ['craft'], null, price: '120.000');

        $this->assertEqualsCanonicalizing(['Amel', 'Cyrine'], $this->names(['city' => 'Tunis']));
        $this->assertEqualsCanonicalizing(['Amel', 'Cyrine'], $this->names(['language' => 'fr']));
        $this->assertSame(['Ben'], $this->names(['interests' => ['art']]));
        $this->assertEqualsCanonicalizing(['Amel', 'Cyrine'], $this->names(['interests' => ['history', 'craft']]));
        // Unrated hosts count as 4.0, so they pass a 4.0 minimum but not 4.5.
        $this->assertEqualsCanonicalizing(['Amel', 'Cyrine'], $this->names(['min_rating' => 4]));
        $this->assertSame(['Amel'], $this->names(['min_rating' => 4.5]));
        $this->assertEqualsCanonicalizing(['Amel', 'Ben'], $this->names(['max_price' => 100]));
        $this->assertSame(['Amel'], $this->names(['max_price' => 50]));
        $this->assertSame(['Cyrine'], $this->names(['city' => 'Tunis', 'language' => 'en', 'max_price' => 150]));
        $this->assertSame([], $this->names(['city' => 'Sousse', 'language' => 'ar']));
        $this->assertSame(['Ben'], $this->names(['q' => 'ben']));
        $this->assertSame(['Cyrine'], $this->names(['q' => 'Cyrine experience']));
        $this->assertSame([], $this->names(['q' => '%']), 'LIKE wildcards are escaped');
    }

    public function test_date_and_group_size_filters(): void
    {
        $open = $this->makeHost('Open');
        $off = $this->makeHost('DayOff');
        $off->hostProfile->blockedDates()->create(['date' => now()->addDays(3)->toDateString()]);

        $this->assertEqualsCanonicalizing(['Open', 'DayOff'], $this->names(['date' => now()->addDays(2)->toDateString()]));
        $this->assertSame(['Open'], $this->names(['date' => now()->addDays(3)->toDateString()]));
        $this->assertSame([], $this->names(['group_size' => 5]), 'experiences allow at most 4 people');
        $this->assertCount(2, $this->names(['group_size' => 4]));
        $this->assertSame($open->hostProfile->id, collect($this->getJson('/api/search')->json('data'))->first()['host_id']);
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->getJson('/api/search?city=Atlantis')->assertStatus(422);
        $this->getJson('/api/search?min_rating=9')->assertStatus(422);
        $this->getJson('/api/search?interests[]=skydiving')->assertStatus(422);
        $this->getJson('/api/search?date=yesterday')->assertStatus(422);
        $this->getJson('/api/search?per_page=500')->assertStatus(422);
    }

    public function test_pagination(): void
    {
        foreach (range(1, 5) as $i) {
            $this->makeHost("Host $i");
        }

        $first = $this->getJson('/api/search?per_page=2')->json();
        $this->assertCount(2, $first['data']);
        $this->assertSame(['page' => 1, 'per_page' => 2, 'total' => 5, 'last_page' => 3], $first['meta']);
        $this->assertCount(1, $this->getJson('/api/search?per_page=2&page=3')->json('data'));
        // Pages never repeat a host.
        $ids = array_merge(...array_map(fn ($p) => array_column($this->getJson("/api/search?per_page=2&page=$p")->json('data'), 'host_id'), [1, 2, 3]));
        $this->assertSame(5, count(array_unique($ids)));
    }

    public function test_every_result_has_a_reason_and_the_two_strongest_are_shown(): void
    {
        $this->makeHost('Plain', 'Sfax', ['en'], ['art']);
        $this->makeHost('Match', 'Tunis', ['fr', 'ar'], ['food', 'history'], 4.9);

        $all = $this->getJson('/api/search?language=fr&interests[]=food&interests[]=history&city=Tunis')->json('data');
        $this->assertCount(1, $all);
        $this->assertCount(2, $all[0]['why_this_host']);
        $this->assertStringContainsString('Français', $all[0]['why_this_host'][0].$all[0]['why_this_host'][1]);

        // No preference at all: still a non-empty reason for each host.
        foreach ($this->getJson('/api/search')->json('data') as $row) {
            $this->assertNotEmpty($row['why_this_host']);
            $this->assertNotEmpty($row['why_this_host'][0]);
        }
    }

    public function test_ranking_uses_the_weights_and_is_deterministic(): void
    {
        $this->makeHost('SpeaksOnly', 'Sfax', ['fr'], ['art'], 4.0);
        $this->makeHost('Both', 'Sfax', ['fr'], ['food'], 4.0);
        $this->makeHost('Neither', 'Sfax', ['en'], ['art'], 4.0);
        $service = app(MatchingService::class);
        $criteria = new SearchCriteria(preferLanguages: ['fr'], preferInterests: ['food']);

        $order = fn () => $service->rank($criteria)->map(fn ($m) => $m->host->display_name)->all();

        $this->assertSame(['Both', 'SpeaksOnly', 'Neither'], $order());
        // Same input, same order, every time.
        $this->assertSame($order(), $order());
        // (0.4 + 0.3 + 0.1 x 4.0/5) / 0.8 = 97.5 -> 98: proximity does not apply without a city.
        $this->assertSame(98, $service->rank($criteria)->first()->score);
    }

    public function test_ties_break_by_rating_then_lowest_id(): void
    {
        $a = $this->makeHost('Tie A', 'Tunis', ['en'], ['food'], 4.2);
        $b = $this->makeHost('Tie B', 'Tunis', ['en'], ['food'], 4.2);
        $c = $this->makeHost('Tie C', 'Tunis', ['en'], ['food'], 4.6);
        // No preferences -> only the rating criterion applies; C scores higher, A and B tie on everything.
        $names = app(MatchingService::class)->rank(new SearchCriteria)->map(fn ($m) => $m->host->display_name)->all();

        $this->assertSame(['Tie C', 'Tie A', 'Tie B'], $names);
    }

    public function test_new_hosts_get_a_default_rating_so_they_are_not_hidden(): void
    {
        $this->makeHost('Newcomer', rating: null);
        $this->makeHost('Rated Low', rating: 3.0);

        $names = app(MatchingService::class)->rank(new SearchCriteria)->map(fn ($m) => $m->host->display_name)->all();

        $this->assertSame(['Newcomer', 'Rated Low'], $names);
    }

    public function test_proximity_prefers_nearer_hosts(): void
    {
        $this->makeHost('In Tunis', 'Tunis');
        $this->makeHost('Sidi Bou Said', 'Sidi Bou Said');
        $this->makeHost('Tozeur', 'Tozeur');
        $criteria = new SearchCriteria(anchorLat: 36.8065, anchorLng: 10.1815, city: null);

        $ranked = app(MatchingService::class)->rank($criteria);

        $this->assertSame(['In Tunis', 'Sidi Bou Said', 'Tozeur'], $ranked->map(fn ($m) => $m->host->display_name)->all());
        $this->assertStringContainsString('Tunis', $ranked->first()->reasons[0]);
    }

    public function test_weights_come_from_config(): void
    {
        $this->makeHost('French', 'Sfax', ['fr'], ['art'], 4.0);
        $this->makeHost('Foodie', 'Sfax', ['en'], ['food'], 4.0);
        $criteria = new SearchCriteria(preferLanguages: ['fr'], preferInterests: ['food']);

        config(['ahla.matching.weights.language' => 0.9, 'ahla.matching.weights.interests' => 0.1]);
        $this->assertSame('French', app(MatchingService::class)->rank($criteria)->first()->host->display_name);

        config(['ahla.matching.weights.language' => 0.1, 'ahla.matching.weights.interests' => 0.9]);
        $this->assertSame('Foodie', app(MatchingService::class)->rank($criteria)->first()->host->display_name);
    }

    public function test_no_result_is_an_empty_list_not_an_error(): void
    {
        $this->assertSame([], $this->names(['city' => 'Tozeur']));
        $this->assertSame(0, $this->getJson('/api/search')->json('meta.total'));
    }

    public function test_profile_languages_and_interests_steer_the_ranking_for_logged_in_tourists(): void
    {
        $this->makeHost('English Cook', 'Tunis', ['en'], ['food'], 4.0);
        $this->makeHost('French Artist', 'Tunis', ['fr'], ['art'], 4.0);
        $tourist = User::factory()->tourist()->create();
        $tourist->languages()->create(['language' => 'fr']);
        $tourist->interests()->create(['interest' => 'art']);

        $this->actingAs($tourist)->get('/search')->assertOk()->assertSeeInOrder(['French Artist', 'English Cook']);
    }

    public function test_query_count_is_constant_and_300_hosts_search_fast(): void
    {
        foreach (range(1, 300) as $i) {
            $this->makeHost("Bulk $i", array_keys(config('ahla.cities'))[$i % 9], [['en', 'fr', 'ar'][$i % 3]], [config('ahla.interests')[$i % 6]], $i % 5 === 0 ? null : 3 + ($i % 20) / 10);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = microtime(true);
        $response = $this->getJson('/api/search?language=fr&interests[]=food&per_page=20');
        $elapsed = microtime(true) - $start;
        $queries = count(DB::getQueryLog());

        $response->assertOk();
        $this->assertLessThan(2.0, $elapsed, 'search must answer within 2 s');
        // Candidates + languages + interests + experiences (+ the pagination count): never one per host.
        $this->assertLessThanOrEqual(8, $queries, "{$queries} queries for ".$response->json('meta.total').' hosts');

        // A second, unfiltered call with 300 hosts costs the same number of queries.
        DB::flushQueryLog();
        $this->getJson('/api/search?per_page=50')->assertOk();
        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }

    public function test_search_page_and_host_page_flow(): void
    {
        $host = $this->makeHost('Page Host', 'Tunis', ['en'], ['food']);
        $this->makeHost('Pending One', status: HostStatus::Pending);

        $this->get('/search?city=Tunis')->assertOk()->assertSee('Page Host')->assertSee('Why this host')->assertSee('Verified')
            ->assertDontSee('Pending One');
        $this->get('/search?city=Tozeur')->assertOk()->assertSee('No verified host matches');

        $this->get("/hosts/{$host->hostProfile->id}")->assertOk()->assertSee('Page Host experience')->assertSee('Log in to book');
        $this->get('/hosts/'.User::factory()->pendingHost()->create()->hostProfile->id)->assertNotFound();

        $tourist = User::factory()->tourist()->create();
        $experience = $host->hostProfile->experiences()->first();
        $this->actingAs($tourist)->get("/hosts/{$host->hostProfile->id}")->assertSee(route('bookings.create', $experience), false);
    }

    public function test_search_page_is_translated_and_rtl(): void
    {
        $this->makeHost('Arabic Host');

        $this->withSession(['locale' => 'ar'])->get('/search')->assertOk()->assertSee('dir="rtl"', false)->assertSee('لماذا هذا المضيف');
        $this->withSession(['locale' => 'fr'])->get('/search')->assertSee('Pourquoi cet hôte');
    }
}
