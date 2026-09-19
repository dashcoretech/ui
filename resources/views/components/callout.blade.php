@props([
    // neutral, success, warning or danger. Anything else — info — is neutral.
    'tone' => 'neutral',
    'heading' => null,
])

@php
    $tone = in_array($tone, ['success', 'warning', 'danger'], true) ? $tone : 'neutral';

    $tones = [
        'neutral' => 'border-dc-metal bg-dc-sunken',
        'success' => 'border-dc-success bg-dc-success-bg',
        'warning' => 'border-dc-warning bg-dc-warning-bg',
        'danger' => 'border-dc-danger bg-dc-danger-bg',
    ];
@endphp

{{-- Something the page needs you to know, in the flash's treatment: a 2px
     rule on its tint. Not a live region — it was there when the page loaded,
     so there is nothing to announce. --}}
<div {{ $attributes->class(['border-l-2 px-4 py-3 text-sm text-dc-ink', $tones[$tone]]) }} data-dc-callout="{{ $tone }}">
    @if ($heading)
        <p class="font-medium">{{ $heading }}</p>
        <div class="mt-1 text-dc-ink-muted">{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
</div>
