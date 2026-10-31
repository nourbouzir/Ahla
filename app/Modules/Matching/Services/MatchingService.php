<?php

namespace App\Modules\Matching\Services;

use App\Modules\Matching\Strategies\InterestStrategy;
use App\Modules\Matching\Strategies\LanguageStrategy;
use App\Modules\Matching\Strategies\MatchingStrategy;
use App\Modules\Matching\Strategies\ProximityStrategy;
use App\Modules\Matching\Strategies\RatingStrategy;
use App\Modules\Matching\Support\MatchResult;
use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The AI-assisted feature (FR-09, FR-10): rank eligible hosts and say why.
 *
 * Score = weighted average of the strategies that apply to this search
 * (weights from config / MATCH_WEIGHT_*). It is plain, local and deterministic:
 * the same input always gives the same order. No LLM (that would need an ADR).
 *
 * Order: score, then rating (unrated hosts count as 4.0), then lowest host id.
 * Reasons: the two criteria that contributed most. A host with no matching
 * criterion still gets a non-empty reason ("Verified local host in <city>").
 */
class MatchingService
{
    /** @var list<MatchingStrategy> in the order used to break ties between equal contributions */
    private array $strategies;

    public function __construct(private readonly HostSearch $search, ?array $strategies = null)
    {
        $this->strategies = $strategies ?? [
            new LanguageStrategy, new InterestStrategy, new ProximityStrategy, new RatingStrategy,
        ];
    }

    /** @return Collection<int, MatchResult> every eligible host, best first (empty = none available) */
    public function rank(SearchCriteria $criteria): Collection
    {
        return $this->search->candidates($criteria)
            ->map(fn (HostProfile $host) => $this->match($host, $criteria))
            ->sort(fn (MatchResult $a, MatchResult $b) => [$b->score, $this->rating($b->host), $b->host->id * -1]
                <=> [$a->score, $this->rating($a->host), $a->host->id * -1])
            ->values();
    }

    public function paginate(SearchCriteria $criteria, int $page = 1, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = max(1, min(50, $perPage ?? (int) config('ahla.matching.per_page')));
        $ranked = $this->rank($criteria);

        return new LengthAwarePaginator(
            $ranked->forPage($page, $perPage)->values(),
            $ranked->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    private function match(HostProfile $host, SearchCriteria $criteria): MatchResult
    {
        $weights = config('ahla.matching.weights');
        $total = 0.0;
        $sum = 0.0;
        $contributions = [];

        foreach ($this->strategies as $order => $strategy) {
            $evaluation = $strategy->evaluate($host, $criteria);
            $weight = (float) ($weights[$strategy->key()] ?? 0);

            if ($evaluation === null || $weight <= 0) {
                continue;
            }

            $total += $weight;
            $sum += $weight * $evaluation->value;

            if ($evaluation->reasonKey !== null && $evaluation->value > 0) {
                $contributions[] = [$weight * $evaluation->value, -$order, $evaluation];
            }
        }

        // Strongest first; equal strength keeps the strategy order, so output is stable.
        usort($contributions, fn ($a, $b) => [$b[0], $b[1]] <=> [$a[0], $a[1]]);

        $reasons = array_map(
            fn ($c) => __("matching::messages.reason.{$c[2]->reasonKey}", $c[2]->reasonParams),
            array_slice($contributions, 0, 2),
        );

        if ($reasons === []) {
            $reasons = [__('matching::messages.reason.verified', ['city' => $host->city])];
        }

        return new MatchResult(
            $host,
            $total > 0 ? (int) round($sum / $total * 100) : 0,
            $reasons,
            $host->experiences->sortBy('id')->values(),
        );
    }

    private function rating(HostProfile $host): float
    {
        return $host->rating_avg !== null ? (float) $host->rating_avg : (float) config('ahla.matching.default_rating');
    }
}
