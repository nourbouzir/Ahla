<?php

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Enums\Role;

/**
 * The one role/action matrix of the platform (SRS FV roles). Every Gate ability
 * is registered from this list, so a controller says `can:booking.request`
 * and never compares roles by hand. Guests (no account) can only browse.
 */
final class Permissions
{
    /** @var array<string, list<Role>> ability => roles that hold it */
    public const MATRIX = [
        'profile.edit' => [Role::Tourist, Role::Host, Role::Admin],
        'host.apply' => [Role::Host],
        'experience.manage' => [Role::Host],
        'availability.manage' => [Role::Host],
        'booking.request' => [Role::Tourist],
        'booking.respond' => [Role::Host],
        'booking.view' => [Role::Tourist, Role::Host, Role::Admin],
        'booking.cancel' => [Role::Tourist, Role::Host],
        'message.send' => [Role::Tourist, Role::Host],
        'admin.verifications' => [Role::Admin],
        'admin.host-status' => [Role::Admin],
        'admin.audit' => [Role::Admin],
        'admin.bookings' => [Role::Admin],
    ];

    public static function allows(Role $role, string $ability): bool
    {
        return in_array($role, self::MATRIX[$ability] ?? [], true);
    }
}
