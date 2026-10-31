<?php

namespace App\Modules\Matching\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Matching\Services\MatchingService;
use App\Modules\Matching\Services\SocialDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoverController extends Controller
{
    private const LOCALES = ['en', 'fr', 'ar'];

    public function __construct(private readonly MatchingService $matching) {}

    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'in:en,fr,ar,it'],
            'interest' => ['nullable', 'in:food,history,craft,nature,photography,art'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'max_price' => ['nullable', 'numeric', 'between:0,1000'],
            'lang' => ['nullable', 'in:en,fr,ar'],
        ]);

        if ($request->is('api/*')) {
            $locale = $filters['lang'] ?? 'en';
        } else {
            if (isset($filters['lang'])) {
                $request->session()->put('locale', $filters['lang']);
            }

            $locale = $request->session()->get('locale', config('app.locale'));
        }

        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'en');

        $criteria = collect($filters)->except('lang')->all();
        $experiences = $this->matching->search($criteria);

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'data' => $experiences->map(fn ($experience) => [
                    'id' => $experience->id,
                    'title' => $experience->title,
                    'city' => $experience->host_city,
                    'price_per_person' => $experience->price_per_person,
                    'duration_hours' => $experience->duration_hours,
                    'host' => $experience->host_name,
                    'rating' => $experience->host_rating_avg,
                    'match_score' => $experience->match_score,
                    'why_this_host' => $experience->match_reasons,
                ]),
            ]);
        }

        return view('matching::discover', [
            'experiences' => $experiences,
            'filters' => $criteria,
            'cities' => $this->matching->cities(),
            'locale' => app()->getLocale(),
            'locals' => app(SocialDemoData::class)->locals(),
            'places' => app(SocialDemoData::class)->places(),
            'conversations' => app(SocialDemoData::class)->conversations(),
        ]);
    }
}
