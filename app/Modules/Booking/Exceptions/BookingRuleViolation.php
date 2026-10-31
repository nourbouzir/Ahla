<?php

namespace App\Modules\Booking\Exceptions;

use RuntimeException;

/**
 * A booking rule said no. $code is a stable key that maps to a translated
 * message at booking::messages.errors.<code>, so controllers never build text.
 */
class BookingRuleViolation extends RuntimeException
{
    public function __construct(public readonly string $rule)
    {
        parent::__construct("Booking rule violated: {$rule}");
    }

    public function userMessage(): string
    {
        return __("booking::messages.errors.{$this->rule}");
    }
}
