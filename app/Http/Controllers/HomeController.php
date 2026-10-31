<?php

namespace App\Http\Controllers;

use App\Modules\Matching\Services\HostSearch;
use App\Modules\Matching\Services\MatchingService;
use App\Modules\Matching\Support\SearchCriteria;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HostSearch $search, MatchingService $matching): View
    {
        $counts = $search->cityCounts();

        // Cities with hosts first (most hosts first), then the rest in configured order.
        $cities = collect(array_keys(config('ahla.cities')))
            ->sortByDesc(fn ($city) => $counts[$city] ?? 0)
            ->mapWithKeys(fn ($city) => [$city => $counts[$city] ?? 0]);

        return view('home', [
            'cities' => $cities,
            'stats' => ['hosts' => array_sum($counts), 'cities' => count(array_filter($counts))],
            // Best-rated verified hosts, ranked by the normal rules (no preferences = rating decides).
            'featured' => $matching->rank(new SearchCriteria)->take(3),
            'allCities' => array_keys(config('ahla.cities')),
            'languages' => config('ahla.languages'),
        ]);
    }
}
