<?php

namespace App\Modules;

/**
 * The one list of modules. bootstrap/providers.php and DatabaseSeeder read it,
 * so adding a module means: create its folder (copy an existing one) and add
 * its name here.
 */
final class Modules
{
    /**
     * Folder names under app/Modules. The order is the seeding order: a module
     * comes after the modules whose tables its tables reference.
     */
    public const NAMES = [
        'Auth',
        'Verification',
        'Experience',
        'Matching',
        'Booking',
        'Payment',
        'Messaging',
        'Notification',
        'Admin',
    ];

    public static function provider(string $name): string
    {
        return "App\\Modules\\{$name}\\{$name}ModuleServiceProvider";
    }

    public static function seeder(string $name): string
    {
        return "App\\Modules\\{$name}\\Database\\Seeders\\{$name}DatabaseSeeder";
    }

    /** @return list<class-string> */
    public static function providers(): array
    {
        return array_map(self::provider(...), self::NAMES);
    }

    /** @return list<class-string> */
    public static function seeders(): array
    {
        return array_map(self::seeder(...), self::NAMES);
    }
}
