<?php

namespace App\Modules\Matching\Strategies;

use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Models\HostProfile;

/** One ranking criterion. Strategies are combined by MatchingService with weights from config. */
interface MatchingStrategy
{
    /** Key used for the weight in config('ahla.matching.weights'). */
    public function key(): string;

    /**
     * Score one host from 0 to 1, or null when this criterion does not apply to
     * the search (for example no interests were given). Null criteria are left
     * out of the average instead of counting as zero.
     */
    public function evaluate(HostProfile $host, SearchCriteria $criteria): ?Evaluation;
}
