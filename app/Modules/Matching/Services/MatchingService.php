<?php

namespace App\Modules\Matching\Services;

use App\Modules\Experience\Models\Experience;
use App\Modules\Experience\Services\ExperienceCatalog;
use App\Modules\Verification\Services\HostDirectory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MatchingService
{
    private const WEIGHTS = [
        'language' => 0.4,
        'interest' => 0.3,
        'city' => 0.2,
        'rating' => 0.1,
    ];

    public function __construct(
        private readonly HostDirectory $hosts,
        private readonly ExperienceCatalog $experiences,
    ) {}

    /**
     * @param  array{q?: string, city?: string, language?: string, interest?: string, min_rating?: numeric-string, max_price?: numeric-string}  $filters
     * @return Collection<int, Experience>
     */
    public function search(array $filters = []): Collection
    {
        $hosts = $this->hosts->verified($filters)->keyBy('id');
        $experiences = $this->experiences->publishedForHosts($hosts->keys()->all(), $filters);

        if ($search = trim($filters['q'] ?? '')) {
            $experiences = $experiences->filter(function (Experience $experience) use ($hosts, $search) {
                $host = $hosts->get($experience->host_profile_id);
                $searchable = Str::lower($experience->title.' '.$experience->description.' '.$host->display_name);

                return Str::contains($searchable, Str::lower($search));
            });
        }

        return $experiences
            ->map(function (Experience $experience) use ($filters, $hosts) {
                $host = $hosts->get($experience->host_profile_id);
                $languages = $host->user->languages->pluck('language')->all();
                $interests = $host->user->interests->pluck('interest')->all();
                $availableWeight = 0;
                $score = 0;

                foreach (self::WEIGHTS as $criterion => $weight) {
                    $matched = match ($criterion) {
                        'language' => isset($filters['language'])
                            ? in_array($filters['language'], $languages, true)
                            : null,
                        'interest' => isset($filters['interest'])
                            ? in_array($filters['interest'], $interests, true)
                            : null,
                        'city' => isset($filters['city'])
                            ? $host->city === $filters['city']
                            : null,
                        'rating' => (float) ($host->rating_avg ?? 4.0) / 5,
                    };

                    if ($matched === null) {
                        continue;
                    }

                    $availableWeight += $weight;
                    $score += (float) $matched * $weight;
                }

                $reasons = [];

                if (isset($filters['language'])) {
                    $reasons[] = __('matching::discover.match_language', [
                        'language' => __('matching::discover.languages.'.$filters['language']),
                    ]);
                }

                if (isset($filters['interest'])) {
                    $reasons[] = __('matching::discover.match_interest', [
                        'interest' => __('matching::discover.interests.'.$filters['interest']),
                    ]);
                }

                if (isset($filters['city'])) {
                    $reasons[] = __('matching::discover.match_city', ['city' => $host->city]);
                }

                if ($host->rating_avg !== null && (float) $host->rating_avg >= 4.8) {
                    $reasons[] = __('matching::discover.match_rating', [
                        'rating' => number_format((float) $host->rating_avg, 1),
                    ]);
                }

                if ($reasons === []) {
                    $reasons[] = __('matching::discover.match_city', ['city' => $host->city]);
                }

                $experience->setAttribute(
                    'match_score',
                    $availableWeight > 0 ? (int) round(($score / $availableWeight) * 100) : 0,
                );
                $experience->setAttribute('match_reasons', array_slice($reasons, 0, 2));
                $experience->setAttribute('host_name', $host->display_name);
                $experience->setAttribute('host_city', $host->city);
                $experience->setAttribute('host_bio', $host->bio);
                $experience->setAttribute('host_languages', $languages);
                $experience->setAttribute('host_rating_avg', $host->rating_avg);
                $experience->setAttribute('host_rating_count', 0);
                $experience->setAttribute('host_avatar_url', $host->photo_url);

                return $experience;
            })
            ->sort(function (Experience $first, Experience $second) {
                return $second->match_score <=> $first->match_score
                    ?: (float) ($second->host_rating_avg ?? 0) <=> (float) ($first->host_rating_avg ?? 0);
            })
            ->values();
    }

    public function cities(): Collection
    {
        return $this->hosts->cities();
    }
}
