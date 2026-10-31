<?php

namespace App\Modules\Experience\Http\Controllers;

use App\Events\ActivityRecorded;
use App\Http\Controllers\Controller;
use App\Modules\Experience\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    private const PLACEHOLDER = '/images/experience-placeholder.svg';

    public function index(Request $request): View
    {
        $profile = $request->user()->hostProfile;

        return view('experience::index', [
            'profile' => $profile,
            'experiences' => $profile ? $profile->experiences()->latest()->get() : collect(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        return $this->requireProfile($request) ?? view('experience::form', ['experience' => new Experience, 'interests' => config('ahla.interests')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->hostProfile;
        abort_if($profile === null, 403);

        $data = $this->validated($request);
        $photo = $this->storePhoto($request) ?? self::PLACEHOLDER;

        $experience = $profile->experiences()->create($data + ['photo_url' => $photo]);
        ActivityRecorded::dispatch('experience', 'created', $request->user()->id, $experience->id, $experience->title);

        return redirect()->route('host.experiences.index')->with('status', __('experience::messages.saved'));
    }

    public function edit(Request $request, Experience $experience): View
    {
        $this->authorizeOwner($request, $experience);

        return view('experience::form', ['experience' => $experience, 'interests' => config('ahla.interests')]);
    }

    public function update(Request $request, Experience $experience): RedirectResponse
    {
        $this->authorizeOwner($request, $experience);

        $data = $this->validated($request);

        if ($photo = $this->storePhoto($request)) {
            $data['photo_url'] = $photo;
        }

        $experience->update($data);
        ActivityRecorded::dispatch('experience', 'updated', $request->user()->id, $experience->id, $experience->title);

        return redirect()->route('host.experiences.index')->with('status', __('experience::messages.saved'));
    }

    /** Hosts unpublish instead of deleting: past bookings keep pointing at the experience. */
    public function toggle(Request $request, Experience $experience): RedirectResponse
    {
        $this->authorizeOwner($request, $experience);
        $experience->update(['published' => ! $experience->published]);
        ActivityRecorded::dispatch('experience', $experience->published ? 'published' : 'unpublished', $request->user()->id, $experience->id, $experience->title);

        return back()->with('status', __('experience::messages.saved'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:2000'],
            'duration_hours' => ['required', 'integer', 'between:1,6'],
            'price_per_person' => ['required', 'numeric', 'between:1,1000', 'regex:/^\d+(\.\d{1,3})?$/'],
            'max_group_size' => ['required', 'integer', 'between:1,6'],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'interests' => ['array'],
            'interests.*' => [Rule::in(config('ahla.interests'))],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset($data['photo']);
        $data['published'] = $request->boolean('published');
        $data['interests'] = array_values($data['interests'] ?? []);
        $data['price_per_person'] = number_format((float) $data['price_per_person'], 3, '.', '');

        return $data;
    }

    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return Storage::disk('public')->url(Storage::disk('public')->putFile('experiences', $request->file('photo')));
    }

    private function authorizeOwner(Request $request, Experience $experience): void
    {
        abort_unless($experience->host_profile_id === $request->user()->hostProfile?->id, 404);
    }

    private function requireProfile(Request $request): ?RedirectResponse
    {
        return $request->user()->hostProfile
            ? null
            : redirect()->route('profile.edit')->withErrors(['title' => __('verification::messages.profile_first')]);
    }
}
