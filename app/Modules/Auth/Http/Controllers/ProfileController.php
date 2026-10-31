<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Support\ProfileVisibility;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileVisibility $visibility) {}

    public function edit(Request $request): View
    {
        $user = $request->user()->load(['languages', 'interests', 'hostProfile']);

        return view('auth::profile-edit', [
            'user' => $user,
            'host' => $user->hostProfile,
            'selectedLanguages' => $user->languages->pluck('language')->all(),
            'selectedInterests' => $user->interests->pluck('interest')->all(),
            'languages' => config('ahla.languages'),
            'interests' => config('ahla.interests'),
            'cities' => array_keys(config('ahla.cities')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isHost = $user->role === Role::Host;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ]{6,20}$/'],
            'preferred_language' => ['required', Rule::in(config('ahla.languages'))],
            'languages' => ['array'],
            'languages.*' => [Rule::in(config('ahla.languages'))],
            'interests' => ['array'],
            'interests.*' => [Rule::in(config('ahla.interests'))],
            'display_name' => [Rule::requiredIf($isHost), 'nullable', 'string', 'max:120'],
            'bio' => [Rule::requiredIf($isHost), 'nullable', 'string', 'max:1500'],
            'city' => [Rule::requiredIf($isHost), 'nullable', Rule::in(array_keys(config('ahla.cities')))],
        ]);

        DB::transaction(function () use ($user, $data, $isHost, $request) {
            $user->update([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'share_phone' => $request->boolean('share_phone'),
                'preferred_language' => $data['preferred_language'],
            ]);

            $user->languages()->delete();
            $user->languages()->createMany(
                collect($data['languages'] ?? [])->unique()->map(fn ($l) => ['language' => $l])->all(),
            );
            $user->interests()->delete();
            $user->interests()->createMany(
                collect($data['interests'] ?? [])->unique()->map(fn ($i) => ['interest' => $i])->all(),
            );

            if ($isHost) {
                [$lat, $lng] = config('ahla.cities')[$data['city']];
                // Status is never taken from the form: a new profile starts as pending.
                $profile = $user->hostProfile ?? new HostProfile(['user_id' => $user->id]);
                $profile->fill([
                    'display_name' => $data['display_name'],
                    'bio' => $data['bio'],
                    'city' => $data['city'],
                    'latitude' => $lat,
                    'longitude' => $lng,
                ])->save();
            }
        });

        // The saved choice wins over the session one from now on.
        $request->session()->put('locale', $data['preferred_language']);

        return redirect()->route('profile.edit')->with('status', __('auth::messages.profile_saved'));
    }

    public function show(Request $request, User $user): View
    {
        abort_unless($this->visibility->canView($request->user(), $user), 403);

        return view('auth::profile-show', [
            'user' => $user->load(['languages', 'interests', 'hostProfile']),
            'showContact' => $this->visibility->canSeeContact($request->user(), $user),
            'showPhone' => $this->visibility->canSeePhone($request->user(), $user),
        ]);
    }
}
