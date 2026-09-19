@props([
    'href',
    // Whether this is the page you are on. By path when not said; a Livewire
    // component re-rendering on its own update route should say.
    'current' => null,
])

@php
    $current ??= trim((string) parse_url($href, PHP_URL_PATH), '/') === trim(request()->path(), '/');
@endphp

{{-- The current tab is ink with a 2px ink rule under it — not the accent,
     which the page's primary action already has. --}}
<a href="{{ $href }}" @if ($current) aria-current="page" @endif
   {{ $attributes->class('-mb-px shrink-0 border-b-2 border-transparent pb-2.5 text-sm text-dc-ink-muted transition-colors duration-150 hover:text-dc-ink aria-[current=page]:border-dc-ink aria-[current=page]:font-medium aria-[current=page]:text-dc-ink') }}>{{ $slot }}</a>
