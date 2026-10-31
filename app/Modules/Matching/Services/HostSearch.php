<?php

namespace App\Modules\Matching\Services;

use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The FILTER half of search (FR-08): which hosts may appear at all. Only
 * verified hosts with at least one published experience are ever returned, and
 * everything is loaded with a fixed number of queries (no N+1).
 */
class HostSearch
{
    /** @return Collection<int, HostProfile> hosts with `experiences` limited to the matching published ones */
    public function candidates(SearchCriteria $c): Collection
    {
        $experienceFilter = function ($q) use ($c) {
            $q->where('published', true)
                ->when($c->maxPrice !== null, fn ($w) => $w->where('price_per_person', '<=', $c->maxPrice))
                ->when($c->groupSize !== null, fn ($w) => $w->where('max_group_size', '>=', $c->groupSize));
        };

        return HostProfile::query()
            ->where('status', HostStatus::Verified->value)
            ->whereHas('experiences', $experienceFilter)
            ->when($c->city, fn ($q, $city) => $q->where('city', $city))
            ->when($c->minRating !== null, fn ($q) => $q->whereRaw('COALESCE(rating_avg, CAST(? AS DECIMAL(3,2))) >= CAST(? AS DECIMAL(3,2))', [config('ahla.matching.default_rating'), $c->minRating]))
            ->when($c->language, fn ($q, $l) => $q->whereHas('user.languages', fn ($w) => $w->where('language', $l)))
            ->when($c->interests !== [], fn ($q) => $q->whereHas('user.interests', fn ($w) => $w->whereIn('interest', $c->interests)))
            ->when($c->date, fn ($q, $date) => $this->availableOn($q, $date))
            ->when($c->q, function ($q, $text) {
                $like = '%'.addcslashes($text, '%_\\').'%';
                $q->where(fn ($w) => $w->where('display_name', 'like', $like)
                    ->orWhere('bio', 'like', $like)
                    ->orWhereHas('experiences', fn ($e) => $e->where('published', true)->where('title', 'like', $like)));
            })
            ->with([
                'user.languages',
                'user.interests',
                'experiences' => fn ($q) => $experienceFilter($q),
            ])
            ->get();
    }

    /** Free that weekday in the weekly hours and not on a blocked date. */
    private function availableOn(Builder $query, string $date): Builder
    {
        $day = Carbon::parse($date);

        return $query
            ->whereHas('availabilities', fn ($w) => $w->where('day_of_week', $day->dayOfWeek))
            ->whereDoesntHave('blockedDates', fn ($w) => $w->whereDate('date', $day->toDateString()));
    }
}
