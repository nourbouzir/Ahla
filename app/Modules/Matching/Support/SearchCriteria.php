<?php

namespace App\Modules\Matching\Support;

use App\Models\User;

/**
 * What a tourist asks for. Two kinds of input:
 *  - FILTERS (hard): a host that does not satisfy them is never shown.
 *  - PREFERENCES (soft): they only change the ranking and the "why this host"
 *    reasons. When the tourist did not type a language or interests, the ones
 *    saved in their profile are used as preferences.
 */
final readonly class SearchCriteria
{
    /**
     * @param  list<string>  $interests  host must share at least one (filter)
     * @param  list<string>  $preferLanguages
     * @param  list<string>  $preferInterests
     */
    public function __construct(
        public ?string $q = null,
        public ?string $city = null,
        public ?string $language = null,
        public array $interests = [],
        public ?float $minRating = null,
        public ?float $maxPrice = null,
        public ?string $date = null,
        public ?int $groupSize = null,
        public array $preferLanguages = [],
        public array $preferInterests = [],
        public ?float $anchorLat = null,
        public ?float $anchorLng = null,
    ) {}

    /** @param array<string, mixed> $input validated request input */
    public static function fromInput(array $input, ?User $viewer = null): self
    {
        $city = $input['city'] ?? null;
        $coords = $city ? (config('ahla.cities')[$city] ?? null) : null;
        $interests = array_values($input['interests'] ?? []);
        $language = $input['language'] ?? null;

        $profileLanguages = $viewer ? $viewer->languages()->pluck('language')->all() : [];
        $profileInterests = $viewer ? $viewer->interests()->pluck('interest')->all() : [];

        return new self(
            q: isset($input['q']) && trim($input['q']) !== '' ? trim($input['q']) : null,
            city: $city,
            language: $language,
            interests: $interests,
            minRating: isset($input['min_rating']) ? (float) $input['min_rating'] : null,
            maxPrice: isset($input['max_price']) ? (float) $input['max_price'] : null,
            date: $input['date'] ?? null,
            groupSize: isset($input['group_size']) ? (int) $input['group_size'] : null,
            preferLanguages: $language ? [$language] : $profileLanguages,
            preferInterests: $interests !== [] ? $interests : $profileInterests,
            anchorLat: $coords[0] ?? null,
            anchorLng: $coords[1] ?? null,
        );
    }

    public function hasAnchor(): bool
    {
        return $this->anchorLat !== null && $this->anchorLng !== null;
    }
}
