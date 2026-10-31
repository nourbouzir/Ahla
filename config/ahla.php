<?php

use App\Contracts\Fakes\NullTranslator;
use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Translation\Translator;
use App\Modules\Experience\Services\WeeklyAvailabilityChecker;
use App\Modules\Matching\Services\MatchingAlternativeHostFinder;
use App\Modules\Payment\Gateways\SandboxPaymentGateway;
use App\Modules\Verification\Services\VerifiedHostEligibility;

return [
    'languages' => ['en', 'fr', 'ar'],
    'interests' => ['food', 'history', 'craft', 'nature', 'photography', 'art'],
    // Cities hosts can pick. Coordinates feed the proximity score of the recommendation.
    'cities' => [
        'Tunis' => [36.8065, 10.1815],
        'Sidi Bou Said' => [36.8704, 10.3470],
        'La Marsa' => [36.8782, 10.3247],
        'Hammamet' => [36.4000, 10.6167],
        'Sousse' => [35.8256, 10.6367],
        'Kairouan' => [35.6781, 10.0963],
        'Sfax' => [34.7406, 10.7603],
        'Djerba' => [33.8750, 10.8590],
        'Tozeur' => [33.9197, 8.1335],
    ],
    'currency' => 'TND',
    'payment' => [
        // Shared secret that signs provider webhooks. Empty = webhook disabled.
        'webhook_secret' => env('AHLA_PAYMENT_WEBHOOK_SECRET', ''),
    ],
    'matching' => [
        // Weights of the ranking criteria (docs/architecture.md section 6). They do not have to sum to 1.
        'weights' => [
            'language' => (float) env('MATCH_WEIGHT_LANGUAGE', 0.4),
            'interests' => (float) env('MATCH_WEIGHT_INTERESTS', 0.3),
            'proximity' => (float) env('MATCH_WEIGHT_PROXIMITY', 0.2),
            'rating' => (float) env('MATCH_WEIGHT_RATING', 0.1),
        ],
        // Hosts without reviews yet count as this rating so new hosts are not hidden.
        'default_rating' => 4.0,
        'proximity_km' => 15,
        'per_page' => 12,
    ],
    'evidence_retention_days' => (int) env('AHLA_EVIDENCE_RETENTION_DAYS', 90),
    'request_ttl_hours' => 12,
    'alternatives_max' => 3,
    'refunds' => [
        'tourist' => [
            'full_refund_hours' => 48,
            'partial_refund_hours' => 24,
            'partial_refund_percent' => 50,
        ],
    ],
    'bindings' => [
        PaymentGateway::class => env(
            'AHLA_PAYMENT_GATEWAY',
            SandboxPaymentGateway::class, // ADR 0005: v1 runs on the sandbox gateway
        ),
        Translator::class => env(
            'AHLA_TRANSLATOR',
            env('APP_ENV') === 'production' ? '' : NullTranslator::class,
        ),
        HostEligibility::class => env('AHLA_HOST_ELIGIBILITY', VerifiedHostEligibility::class),
        AvailabilityChecker::class => env('AHLA_AVAILABILITY_CHECKER', WeeklyAvailabilityChecker::class),
        AlternativeHostFinder::class => env('AHLA_ALTERNATIVE_HOST_FINDER', MatchingAlternativeHostFinder::class),
    ],
];
