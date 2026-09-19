@props([
    'status' => session('status'),
    // Apps flash 'success' as often as 'status'; both are the success tone.
    'success' => session('success'),
    'warning' => session('warning'),
    'error' => session('error'),
    // The validation errors, from the shared bag. :errors="false" leaves them
    // to the form, for a page whose fields already say what is wrong.
    'errors' => null,
])

@php
    $bag = $errors ?: null;

    // Fortify and the starter kits flash codes ('verification-link-sent',
    // 'profile-updated') for the page that set them to word in its own way.
    // A code is shown only when the app has a translation for it; otherwise
    // the page's own message is the one to read, and the raw code is noise.
    $sentence = function ($message) {
        if (! is_string($message) || ! preg_match('/^[a-z0-9]+(?:[-_.][a-z0-9]+)+$/', $message)) {
            return $message;
        }

        return __($message) !== $message ? __($message) : null;
    };

    $successes = array_unique(array_filter([$sentence($status), $sentence($success)], 'filled'));
@endphp

{{-- Muted semantic colour on its -bg tint, with a 2px rule on the left rather
     than a filled box — the same treatment as a status tag, at page width. --}}
@foreach ($successes as $key => $message)
    <div class="mb-6 border-l-2 border-dc-success bg-dc-success-bg px-4 py-3 text-sm text-dc-ink" role="status" data-dc-flash="{{ $key === 0 ? 'status' : 'success' }}">
        {{ $message }}
    </div>
@endforeach

@if ($warning)
    <div class="mb-6 border-l-2 border-dc-warning bg-dc-warning-bg px-4 py-3 text-sm text-dc-ink" role="status" data-dc-flash="warning">
        {{ $warning }}
    </div>
@endif

@if ($error)
    <div class="mb-6 border-l-2 border-dc-danger bg-dc-danger-bg px-4 py-3 text-sm text-dc-ink" role="alert" data-dc-flash="error">
        {{ $error }}
    </div>
@endif

@if ($bag && $bag->any())
    <div class="mb-6 border-l-2 border-dc-danger bg-dc-danger-bg px-4 py-3 text-sm text-dc-ink" role="alert" data-dc-flash="errors">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($bag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
