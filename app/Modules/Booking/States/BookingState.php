<?php

namespace App\Modules\Booking\States;

use App\Modules\Booking\Enums\BookingStatus;

/**
 * State pattern for a booking (ADR 0004): the only place that says which status
 * may follow which. requested -> confirmed | declined | expired;
 * confirmed -> cancelled | completed. Everything else is final.
 */
abstract class BookingState
{
    /** @return list<BookingStatus> */
    abstract public function next(): array;

    public function canTransitionTo(BookingStatus $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    public static function for(BookingStatus $status): self
    {
        return match ($status) {
            BookingStatus::Requested => new RequestedState,
            BookingStatus::Confirmed => new ConfirmedState,
            BookingStatus::Declined, BookingStatus::Expired, BookingStatus::Cancelled, BookingStatus::Completed => new FinalState,
        };
    }
}
