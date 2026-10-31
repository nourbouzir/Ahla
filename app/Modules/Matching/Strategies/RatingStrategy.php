<?php

namespace App\Modules\Matching\Strategies;

use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Models\HostProfile;

/** Average rating divided by 5. Hosts with no rating yet count as the configured default (4.0). */
class RatingStrategy implements MatchingStrategy
{
    public function key(): string
    {
        return 'rating';
    }

    public function evaluate(HostProfile $host, SearchCriteria $criteria): ?Evaluation
    {
        $rating = $host->rating_avg !== null ? (float) $host->rating_avg : (float) config('ahla.matching.default_rating');

        // Only a real, high rating is worth showing as a reason.
        $reason = $host->rating_avg !== null && $rating >= 4.5 ? 'rating' : null;

        return new Evaluation($rating / 5, $reason, ['rating' => number_format($rating, 1)]);
    }
}
