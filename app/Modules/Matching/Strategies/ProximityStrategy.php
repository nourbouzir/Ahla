<?php

namespace App\Modules\Matching\Strategies;

use App\Modules\Matching\Support\SearchCriteria;
use App\Modules\Verification\Models\HostProfile;

/** 1 - distance/15 km when both sides have coordinates, otherwise 1 for the same city and 0 for another. */
class ProximityStrategy implements MatchingStrategy
{
    public function key(): string
    {
        return 'proximity';
    }

    public function evaluate(HostProfile $host, SearchCriteria $criteria): ?Evaluation
    {
        if (! $criteria->hasAnchor() && $criteria->city === null) {
            return null;
        }

        if ($criteria->hasAnchor() && $host->latitude !== null && $host->longitude !== null) {
            $km = $this->distanceKm($criteria->anchorLat, $criteria->anchorLng, (float) $host->latitude, (float) $host->longitude);
            $value = max(0.0, 1 - $km / (float) config('ahla.matching.proximity_km'));

            return $value > 0
                ? new Evaluation($value, $km < 1 ? 'nearby' : 'distance', ['city' => $host->city, 'km' => (int) round($km)])
                : new Evaluation(0.0);
        }

        return $host->city === $criteria->city
            ? new Evaluation(1.0, 'nearby', ['city' => $host->city])
            : new Evaluation(0.0);
    }

    /** Haversine distance in kilometres. */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * asin(min(1.0, sqrt($a)));
    }
}
