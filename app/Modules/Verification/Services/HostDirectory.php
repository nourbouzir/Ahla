<?php

namespace App\Modules\Verification\Services;

use App\Modules\Verification\Models\HostProfile;
use Illuminate\Support\Collection;

class HostDirectory
{
    public function verified(array $filters = []): Collection
    {
        $query = HostProfile::query()->where('status', 'verified');

        if ($city = $filters['city'] ?? null) {
            $query->where('city', $city);
        }

        if ($language = $filters['language'] ?? null) {
            $query->whereJsonContains('languages', $language);
        }

        if ($interest = $filters['interest'] ?? null) {
            $query->whereJsonContains('interests', $interest);
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating_avg', '>=', $filters['min_rating']);
        }

        return $query->get();
    }

    public function cities(): Collection
    {
        return HostProfile::query()
            ->where('status', 'verified')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    public function findByDisplayName(string $displayName): HostProfile
    {
        return HostProfile::query()->where('display_name', $displayName)->firstOrFail();
    }
}
