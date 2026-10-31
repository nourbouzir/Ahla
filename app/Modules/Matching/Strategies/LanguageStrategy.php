<?php

namespace App\Modules\Matching\Strategies;

use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Models\HostProfile;

/** 1 when the host speaks one of the tourist's languages, 0.5 when not (language ranks, it does not exclude). */
class LanguageStrategy implements MatchingStrategy
{
    public function key(): string
    {
        return 'language';
    }

    public function evaluate(HostProfile $host, SearchCriteria $criteria): ?Evaluation
    {
        if ($criteria->preferLanguages === []) {
            return null;
        }

        $shared = array_values(array_intersect($criteria->preferLanguages, $host->user->languages->pluck('language')->all()));

        if ($shared === []) {
            return new Evaluation(0.5);
        }

        return new Evaluation(1.0, 'language', ['languages' => implode(', ', array_map(fn ($l) => __("common.languages.$l"), $shared))]);
    }
}
