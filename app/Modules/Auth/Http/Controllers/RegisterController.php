<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth::register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            // Only the two public roles can be chosen here. Admins are created by hand.
            'role' => ['required', Rule::in([Role::Tourist->value, Role::Host->value])],
        ]);

        $user = new User(collect($data)->only(['name', 'email', 'password'])->all());
        $user->role = Role::from($data['role']);
        $user->preferred_language = app()->getLocale();
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('status', __('auth::messages.registered'));
    }
}
