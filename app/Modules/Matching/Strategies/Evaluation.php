<?php

namespace App\Modules\Matching\Strategies;

/** A strategy's answer: a 0..1 value and, when it helps the reader, a reason to show. */
final readonly class Evaluation
{
    /** @param array<string, string|int|float> $reasonParams */
    public function __construct(
        public float $value,
        public ?string $reasonKey = null,
        public array $reasonParams = [],
    ) {}
}
