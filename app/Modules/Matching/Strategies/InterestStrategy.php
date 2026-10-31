<?php

namespace App\Modules\Matching\Strategies;

use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Models\HostProfile;

/** Share of the tourist's interests that the host also has. */
class InterestStrategy implements MatchingStrategy
{
    public function key(): string
    {
        return 'interests';
    }

    public function evaluate(HostProfile $host, SearchCriteria $criteria): ?Evaluation
    {
        if ($criteria->preferInterests === []) {
            return null;
        }

        $shared = array_values(array_intersect($criteria->preferInterests, $host->user->interests->pluck('interest')->all()));
        $value = count($shared) / count($criteria->preferInterests);

        if ($shared === []) {
            return new Evaluation(0.0);
        }

        return new Evaluation($value, 'interests', ['interests' => implode(', ', array_map(fn ($i) => __("auth::messages.interest.$i"), $shared))]);
    }
}
