<?php

namespace App\Modules\Admin;

use App\Events\ActivityRecorded;
use App\Events\MessageSent;
use App\Models\User;
use App\Modules\Admin\Services\ActivityLogger;
use App\Modules\ModuleServiceProvider;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

class AdminModuleServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // NFR-12: successful logins store no IP; a failed attempt stores only the IP (security metadata), never the typed e-mail or password.
        // The admin timeline listens; no other module has to know it exists.
        $log = fn () => $this->app->make(ActivityLogger::class);

        Event::listen(Login::class, fn (Login $e) => $log()->record('session', 'logged_in', $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? $log()->record('session', 'logged_out', $e->user) : null);
        Event::listen(Registered::class, fn (Registered $e) => $log()->record('session', 'registered', $e->user, null, $e->user->role->value));
        Event::listen(Failed::class, function (Failed $e) use ($log) {
            // Known account: attach it. Unknown e-mail: still logged (id 0) so brute-force attempts are visible.
            $user = $e->user ?? User::where('email', $e->credentials['email'] ?? '')->first();
            $log()->record('session', 'login_failed', null, $user?->id ?? 0, 'from '.request()->ip());
        });
        // Who wrote to whom, never what: message text stays out of the log.
        Event::listen(MessageSent::class, fn (MessageSent $e) => $log()->record('message', 'sent', User::find($e->senderId), $e->messageId, 'booking #'.$e->bookingId));
        Event::listen(ActivityRecorded::class, fn (ActivityRecorded $e) => $log()->record($e->entity, $e->action, $e->actorId ? User::find($e->actorId) : null, $e->subjectId, $e->detail));
    }
}
