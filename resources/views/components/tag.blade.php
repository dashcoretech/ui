@props([
    // neutral, success, warning or danger. Anything else is neutral.
    'tone' => null,
    // Flux's badge colours, so a page moving off Flux keeps its meaning.
    'color' => null,
])

@php
    $tone ??= match ($color) {
        'green', 'emerald', 'lime', 'teal' => 'success',
        'yellow', 'amber', 'orange' => 'warning',
        'red', 'rose' => 'danger',
        default => 'neutral',
    };

    $tone = in_array($tone, ['success', 'warning', 'danger'], true) ? $tone : 'neutral';
@endphp

{{-- A status tag (.dc-tag), not a pill. --}}
<span {{ $attributes->class(['dc-tag', "dc-tag-{$tone}" => $tone !== 'neutral']) }} data-dc-tag="{{ $tone }}">{{ $slot }}</span>
