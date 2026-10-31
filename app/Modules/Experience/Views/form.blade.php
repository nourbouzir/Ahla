<x-layouts.app :title="__('experience::messages.form')">
    @php($editing = $experience->exists)
    <form method="POST" enctype="multipart/form-data" class="card mx-auto max-w-xl"
        action="{{ $editing ? route('host.experiences.update', $experience) : route('host.experiences.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <h1 class="text-2xl font-bold">{{ __('experience::messages.form') }}</h1>

        <x-input name="title" :label="__('experience::messages.f_title')" :value="$experience->title" required />
        <x-input name="category" :label="__('experience::messages.f_category')" :value="$experience->category" required />
        <div class="mt-4">
            <label class="label" for="description">{{ __('experience::messages.f_description') }} *</label>
            <textarea class="field" id="description" name="description" rows="5" required>{{ old('description', $experience->description) }}</textarea>
        </div>
        <x-input name="duration_hours" type="number" min="1" max="6" :label="__('experience::messages.f_duration')" :value="$experience->duration_hours ?? 2" required />
        <x-input name="price_per_person" type="number" step="0.001" min="1" max="1000" :label="__('experience::messages.f_price')" :value="$experience->price_per_person" required />
        <x-input name="max_group_size" type="number" min="1" max="6" :label="__('experience::messages.f_group')" :value="$experience->max_group_size ?? 4" required />
        <x-input name="meeting_point" :label="__('experience::messages.f_meeting')" :value="$experience->meeting_point" />

        <fieldset class="mt-4">
            <legend class="label">{{ __('auth::messages.interests') }}</legend>
            @foreach ($interests as $interest)
                <label class="mr-4 inline-flex items-center gap-2 py-2">
                    <input type="checkbox" name="interests[]" value="{{ $interest }}" @checked(in_array($interest, old('interests', $experience->interests ?? [])))>
                    {{ __("auth::messages.interest.$interest") }}
                </label>
            @endforeach
        </fieldset>

        <div class="mt-4">
            <label class="label" for="photo">{{ __('experience::messages.f_photo') }}</label>
            <input class="field" type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
            @error('photo')<p class="error-text">{{ $message }}</p>@enderror
        </div>

        <label class="mt-4 flex items-center gap-2"><input type="checkbox" name="published" value="1" @checked(old('published', $experience->published))> {{ __('experience::messages.published') }}</label>
        <button class="btn btn-primary mt-6" type="submit">{{ __('common.actions.save') }}</button>
    </form>
</x-layouts.app>
