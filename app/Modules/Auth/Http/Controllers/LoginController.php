<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('auth::login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Throttle per email + IP so one attacker cannot hammer one account.
        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('auth::messages.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            // Same message for unknown email and wrong password: no account enumeration.
            throw ValidationException::withMessages(['email' => __('auth::messages.failed')]);
        }

        RateLimiter::clear($key);

        if (Auth::user()->disabled_at !== null) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => __('auth::messages.disabled')]);
        }

        $request->session()->regenerate();

        // Guides and admins land in their own space; everybody else where they were going.
        $home = match (Auth::user()->role->value) {
            'host' => route('host.dashboard'),
            'admin' => route('admin.dashboard'),
            default => route('home'),
        };

        return redirect()->intended($home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
