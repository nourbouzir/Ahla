<?php

use App\Contracts\Fakes\FakeAlternativeHostFinder;
use App\Contracts\Fakes\FakeAvailabilityChecker;
use App\Contracts\Fakes\FakeHostEligibility;
use App\Contracts\Fakes\FakePaymentGateway;
use App\Contracts\Fakes\NullTranslator;
use App\Contracts\Hosts\AlternativeHostFinder;
use App\Contracts\Hosts\AvailabilityChecker;
use App\Contracts\Hosts\HostEligibility;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Translation\Translator;

return [
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
            env('APP_ENV') === 'production' ? '' : FakePaymentGateway::class,
        ),
        Translator::class => env(
            'AHLA_TRANSLATOR',
            env('APP_ENV') === 'production' ? '' : NullTranslator::class,
        ),
        HostEligibility::class => env(
            'AHLA_HOST_ELIGIBILITY',
            env('APP_ENV') === 'production' ? '' : FakeHostEligibility::class,
        ),
        AvailabilityChecker::class => env(
            'AHLA_AVAILABILITY_CHECKER',
            env('APP_ENV') === 'production' ? '' : FakeAvailabilityChecker::class,
        ),
        AlternativeHostFinder::class => env(
            'AHLA_ALTERNATIVE_HOST_FINDER',
            env('APP_ENV') === 'production' ? '' : FakeAlternativeHostFinder::class,
        ),
    ],
];
