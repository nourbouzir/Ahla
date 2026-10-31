<?php

namespace App\Modules\Auth\Support;

use App\Contracts\Bookings\ConfirmedBookingLookup;
use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Verification\Enums\HostStatus;

/**
 * Who may see what on a profile.
 *  - Name, languages, interests: visible whenever the profile itself is visible.
 *  - Phone and email: only the owner, admins, and the other side of a CONFIRMED booking.
 */
class ProfileVisibility
{
    public function __construct(private readonly ConfirmedBookingLookup $bookings) {}

    public function canView(?User $viewer, User $subject): bool
    {
        if ($viewer && ($viewer->is($subject) || $viewer->role === Role::Admin)) {
            return true;
        }

        // A host profile is public only once verified (FR-07).
        if ($subject->role === Role::Host) {
            return $subject->hostProfile?->status === HostStatus::Verified
                || ($viewer && $this->bookings->haveAnyBooking($viewer, $subject));
        }

        // A tourist profile is private except to a host they have a booking with.
        return $viewer !== null && $this->bookings->haveAnyBooking($viewer, $subject);
    }

    /** The phone number follows the same rule as the e-mail, plus the owner's own choice (FR-04). */
    public function canSeePhone(?User $viewer, User $subject): bool
    {
        if ($viewer !== null && ($viewer->is($subject) || $viewer->role === Role::Admin)) {
            return true;
        }

        return $subject->share_phone && $this->canSeeContact($viewer, $subject);
    }

    public function canSeeContact(?User $viewer, User $subject): bool
    {
        if ($viewer === null) {
            return false;
        }

        return $viewer->is($subject)
            || $viewer->role === Role::Admin
            || $this->bookings->haveConfirmedBooking($viewer, $subject);
    }
}
