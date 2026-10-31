<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoHostAccountService
{
    public function ensure(string $name, string $email): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;

        if (! $user->exists) {
            $user->password = Hash::make(Str::random(48));
        }

        $user->forceFill([
            'role' => 'host',
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
