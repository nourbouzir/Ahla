<x-layouts.app :title="__('auth::messages.profile_title')">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card mx-auto max-w-xl">
        @csrf @method('PUT')
        <h1 class="text-2xl font-bold">{{ __('auth::messages.profile_title') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $user->email }}</p>

        <div class="mt-4 flex items-center gap-4">
            <x-avatar :user="$user" size="h-20 w-20 text-2xl" :label="__('auth::messages.avatar')" />
            <div>
                <label class="label" for="avatar">{{ __('auth::messages.avatar') }}</label>
                <input class="mt-1 block text-sm" type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" aria-describedby="avatar-hint">
                <p id="avatar-hint" class="mt-1 text-sm text-slate-600">{{ __('auth::messages.avatar_hint') }}</p>
                @error('avatar')<p class="error-text">{{ $message }}</p>@enderror
                @if ($user->avatar_path)
                    <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_avatar" value="1"> {{ __('auth::messages.avatar_remove') }}</label>
                @endif
            </div>
        </div>

        <x-input name="name" :label="__('auth::messages.name')" :value="$user->name" autocomplete="name" required />
        <x-input name="phone" type="tel" :label="__('auth::messages.phone')" :value="$user->phone" autocomplete="tel" />
        <p class="mt-1 text-sm text-slate-600">{{ __('auth::messages.phone_hint') }}</p>
        <label class="mt-3 flex items-center gap-2"><input type="checkbox" name="share_phone" value="1" @checked(old('share_phone', $user->share_phone))> {{ __('auth::messages.share_phone') }}</label>

        <div class="mt-4">
            <label class="label" for="preferred_language">{{ __('auth::messages.preferred_language') }}</label>
            <select class="field" id="preferred_language" name="preferred_language">
                @foreach ($languages as $code)
                    <option value="{{ $code }}" @selected(old('preferred_language', $user->preferred_language) === $code)>{{ __("common.languages.$code") }}</option>
                @endforeach
            </select>
        </div>

        <fieldset class="mt-4">
            <legend class="label">{{ __('auth::messages.spoken_languages') }}</legend>
            @foreach ($languages as $code)
                <label class="mr-4 inline-flex items-center gap-2 py-2">
                    <input type="checkbox" name="languages[]" value="{{ $code }}" @checked(in_array($code, old('languages', $selectedLanguages)))>
                    {{ __("common.languages.$code") }}
                </label>
            @endforeach
        </fieldset>

        <fieldset class="mt-4">
            <legend class="label">{{ __('auth::messages.interests') }}</legend>
            @foreach ($interests as $interest)
                <label class="mr-4 inline-flex items-center gap-2 py-2">
                    <input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, old('interests', $selectedInterests)))>
                    {{ __("auth::messages.interest.$interest") }}
                </label>
            @endforeach
        </fieldset>

        @if ($user->role->value === 'host')
            <h2 class="mt-6 text-lg font-semibold">{{ __('auth::messages.host_section') }}</h2>
            <p class="text-sm text-slate-600">{{ __('auth::messages.host_status') }}: <span class="badge">{{ __('auth::messages.status.'.($host?->status->value ?? 'pending')) }}</span></p>
            @if (Route::has('host.verification.show'))
                <p class="mt-2"><a class="underline" href="{{ route('host.verification.show') }}">{{ __('verification::messages.title') }}</a></p>
            @endif
            <x-input name="display_name" :label="__('auth::messages.display_name')" :value="$host?->display_name" required />
            <div class="mt-4">
                <label class="label" for="bio">{{ __('auth::messages.bio') }} *</label>
                <textarea class="field" id="bio" name="bio" rows="4" required>{{ old('bio', $host?->bio) }}</textarea>
            </div>
            <div class="mt-4">
                <label class="label" for="city">{{ __('auth::messages.city') }} *</label>
                <select class="field" id="city" name="city" required>
                    <option value=""></option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(old('city', $host?->city) === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <button class="btn btn-primary mt-6" type="submit">{{ __('common.actions.save') }}</button>
    </form>
</x-layouts.app>
