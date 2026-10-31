<?php

namespace App\Http\Controllers;

use App\Modules\Matching\Services\HostSearch;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HostSearch $search): View
    {
        $counts = $search->cityCounts();

        // Cities with hosts first (most hosts first), then the rest in configured order; six are featured.
        $cities = collect(array_keys(config('ahla.cities')))
            ->sortByDesc(fn ($city) => $counts[$city] ?? 0)
            ->take(6)
            ->mapWithKeys(fn ($city) => [$city => $counts[$city] ?? 0]);

        return view('home', ['cities' => $cities]);
    }
}
