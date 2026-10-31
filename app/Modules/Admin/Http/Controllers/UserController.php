<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Admin\Services\AuditLogger;
use App\Modules\Auth\Enums\Role;
use App\Modules\Booking\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = Role::tryFrom((string) $request->query('role'));
        $state = in_array($request->query('state'), ['active', 'disabled'], true) ? $request->query('state') : null;
        $q = trim((string) $request->query('q'));

        $users = User::query()
            ->addSelect(['users.*', 'bookings_count' => Booking::query()->selectRaw('COUNT(*)')->whereRaw('(tourist_id = users.id OR host_id = users.id)')])
            ->when($role, fn ($query) => $query->where('role', $role->value))
            ->when($state === 'active', fn ($query) => $query->whereNull('disabled_at'))
            ->when($state === 'disabled', fn ($query) => $query->whereNotNull('disabled_at'))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin::users.index', ['users' => $users, 'role' => $role?->value, 'state' => $state, 'q' => $q]);
    }

    public function show(User $user): View
    {
        return view('admin::users.show', [
            'person' => $user->load(['hostProfile', 'languages', 'interests']),
            'bookings' => Booking::query()->with(['experience', 'payment'])->where(fn ($q) => $q->where('tourist_id', $user->id)->orWhere('host_id', $user->id))
                ->latest('id')->limit(10)->get(),
            'history' => AuditLog::query()->with('actor')->where(fn ($q) => $q->where(fn ($w) => $w->where('entity', 'user')->where('entity_id', $user->id))->orWhere('actor_id', $user->id))
                ->latest('created_at')->latest('id')->limit(10)->get(),
        ]);
    }

    public function disable(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        // Never lock yourself or another admin out: that would remove the people who can undo it.
        if ($user->role === Role::Admin) {
            return back()->withErrors(['reason' => __('admin::dash.users.cannot_admin')]);
        }

        DB::transaction(function () use ($user, $data, $request, $audit) {
            $user->forceFill(['disabled_at' => now(), 'disabled_reason' => $data['reason']])->save();
            // Log them out everywhere right now.
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $audit->record('user', $user->id, 'active', 'disabled', $request->user(), $data['reason']);
        });

        return back()->with('status', __('admin::dash.users.disabled_done'));
    }

    public function enable(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($user, $request, $audit) {
            $user->forceFill(['disabled_at' => null, 'disabled_reason' => null])->save();
            $audit->record('user', $user->id, 'disabled', 'active', $request->user(), 'account re-enabled');
        });

        return back()->with('status', __('admin::dash.users.enabled_done'));
    }
}
