<?php

namespace App\Modules\Matching\Support;

use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Support\Collection;

/** One ranked host: score 0-100, the "why this host" reasons (never empty) and the host's published experiences. */
final readonly class MatchResult
{
    /**
     * @param  list<string>  $reasons
     * @param  Collection<int, Experience>  $experiences
     */
    public function __construct(
        public HostProfile $host,
        public int $score,
        public array $reasons,
        public Collection $experiences,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'host_id' => $this->host->id,
            'name' => $this->host->display_name,
            'city' => $this->host->city,
            'verified' => true,
            'rating' => $this->host->rating_avg !== null ? (float) $this->host->rating_avg : null,
            'languages' => $this->host->user->languages->pluck('language')->values()->all(),
            'interests' => $this->host->user->interests->pluck('interest')->values()->all(),
            'score' => $this->score,
            'why_this_host' => $this->reasons,
            'experiences' => $this->experiences->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'duration_hours' => $e->duration_hours,
                'price_per_person' => number_format((float) $e->price_per_person, 3, '.', ''),
            ])->values()->all(),
        ];
    }
}
