<?php

namespace App\Modules\Auth\Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Accounts every environment needs (the admin) and, outside production, demo
 * tourists. The admin password comes from AHLA_ADMIN_PASSWORD; if it is empty
 * in production a random one is generated and printed once.
 */
class AuthDatabaseSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $this->account('Ahla Admin', 'admin@ahla.test', Role::Admin, $this->adminPassword());

        if (app()->isProduction()) {
            return;
        }

        $this->account('Lea Martin', 'tourist@ahla.test', Role::Tourist, self::DEMO_PASSWORD, 'fr', ['fr', 'en'], ['food', 'history']);
        $this->account('Sam Carter', 'sam@ahla.test', Role::Tourist, self::DEMO_PASSWORD, 'en', ['en'], ['photography', 'nature']);
        $this->account('Nora Haddad', 'nora@ahla.test', Role::Tourist, self::DEMO_PASSWORD, 'fr', ['fr', 'ar'], ['craft', 'art']);
        $this->account('Marco Rossi', 'marco@ahla.test', Role::Tourist, self::DEMO_PASSWORD, 'en', ['en'], ['food', 'history']);
        $this->account('Yuki Tanaka', 'yuki@ahla.test', Role::Tourist, self::DEMO_PASSWORD, 'en', ['en'], ['photography', 'art']);
        $this->account('Hannah Weber', 'hannah@ahla.test', Role::Tourist, self::DEMO_PASSWORD, 'en', ['en', 'fr'], ['nature', 'food']);
    }

    private function adminPassword(): string
    {
        $configured = (string) env('AHLA_ADMIN_PASSWORD', '');

        if ($configured !== '') {
            return $configured;
        }

        if (! app()->isProduction()) {
            return self::DEMO_PASSWORD;
        }

        $generated = Str::password(20);
        $this->command?->warn("Generated admin password (shown once): {$generated}");

        return $generated;
    }

    /**
     * @param  list<string>  $languages
     * @param  list<string>  $interests
     */
    public function account(string $name, string $email, Role $role, string $password, string $locale = 'en', array $languages = [], array $interests = []): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = $password;       // hashed by the model cast
        $user->role = $role;
        $user->preferred_language = $locale;
        $user->email_verified_at = now();
        $user->save();

        $user->languages()->delete();
        $user->languages()->createMany(array_map(fn ($l) => ['language' => $l], $languages));
        $user->interests()->delete();
        $user->interests()->createMany(array_map(fn ($i) => ['interest' => $i], $interests));

        return $user;
    }
}
