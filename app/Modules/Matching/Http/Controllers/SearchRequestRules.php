<?php

namespace App\Modules\Matching\Http\Controllers;

use Illuminate\Validation\Rule;

/** The single list of accepted search inputs, shared by the page and the JSON API. */
final class SearchRequestRules
{
    /** @return array<string, array<int, mixed>> */
    public static function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', Rule::in(array_keys(config('ahla.cities')))],
            'language' => ['nullable', Rule::in(config('ahla.languages'))],
            'interests' => ['nullable', 'array', 'max:6'],
            'interests.*' => [Rule::in(config('ahla.interests'))],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'max_price' => ['nullable', 'numeric', 'between:0,1000'],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'group_size' => ['nullable', 'integer', 'between:1,6'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
