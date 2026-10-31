<?php

namespace App\Modules\Experience\Services;

use App\Modules\Experience\Models\Experience;
use Illuminate\Support\Collection;

class ExperienceCatalog
{
    public function publishedForHosts(array $hostProfileIds, array $filters = []): Collection
    {
        if ($hostProfileIds === []) {
            return collect();
        }

        $query = Experience::query()
            ->whereIn('host_profile_id', $hostProfileIds)
            ->where('published', true);

        if (isset($filters['max_price'])) {
            $query->where('price_per_person', '<=', $filters['max_price']);
        }

        return $query->get();
    }
}
