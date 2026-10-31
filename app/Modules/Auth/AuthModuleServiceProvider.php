<?php

namespace App\Modules\Auth;

use App\Models\User;
use App\Modules\Auth\Support\Permissions;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthModuleServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // One Gate ability per row of the role/action matrix.
        foreach (array_keys(Permissions::MATRIX) as $ability) {
            Gate::define($ability, fn (User $user) => Permissions::allows($user->role, $ability));
        }
    }
}
