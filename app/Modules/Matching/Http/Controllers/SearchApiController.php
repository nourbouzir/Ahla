<?php

namespace App\Modules\Matching\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Matching\Services\MatchingService;
use App\Modules\Matching\Support\SearchCriteria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/search - verified hosts, filtered, ranked, each with its reasons. */
class SearchApiController extends Controller
{
    public function __invoke(Request $request, MatchingService $matching): JsonResponse
    {
        $input = $request->validate(SearchRequestRules::rules());
        $page = $matching->paginate(SearchCriteria::fromInput($input, $request->user()), (int) ($input['page'] ?? 1), $input['per_page'] ?? null);

        return response()->json([
            'data' => $page->getCollection()->map->toArray()->values(),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }
}
