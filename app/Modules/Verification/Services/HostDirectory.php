<?php

namespace App\Modules\Verification\Services;

use App\Modules\Verification\Models\HostProfile;
use Illuminate\Support\Collection;

class HostDirectory
{
    public function verified(array $filters = []): Collection
    {
        $query = HostProfile::query()
            ->with(['user.languages', 'user.interests'])
            ->where('status', 'verified');

        if ($city = $filters['city'] ?? null) {
            $query->where('city', $city);
        }

        if ($language = $filters['language'] ?? null) {
            $query->whereHas('user.languages', fn ($languages) => $languages->where('language', $language));
        }

        if ($interest = $filters['interest'] ?? null) {
            $query->whereHas('user.interests', fn ($interests) => $interests->where('interest', $interest));
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
            ->with(['user.languages', 'user.interests'])
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    public function findByDisplayName(string $displayName): HostProfile
    {
        return HostProfile::query()->where('display_name', $displayName)->firstOrFail();
    }
}
