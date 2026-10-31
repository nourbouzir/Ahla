<?php

namespace App\Modules\Matching\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Matching\Services\MatchingService;
use App\Modules\Matching\Support\SearchCriteria;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchPageController extends Controller
{
    public function __invoke(Request $request, MatchingService $matching): View
    {
        $input = $request->validate(SearchRequestRules::rules());
        $searched = $request->hasAny(['q', 'city', 'language', 'interests', 'min_rating', 'max_price', 'date', 'group_size']);

        return view('matching::search', [
            'input' => $input,
            'searched' => $searched,
            'results' => $matching->paginate(SearchCriteria::fromInput($input, $request->user()), (int) ($input['page'] ?? 1)),
            'cities' => array_keys(config('ahla.cities')),
            'languages' => config('ahla.languages'),
            'interests' => config('ahla.interests'),
        ]);
    }
}
