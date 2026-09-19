@props([
    // primary, secondary, danger or ghost. One primary per page.
    'variant' => 'secondary',
    // sm for a button in a table row or beside a heading.
    'size' => null,
    // A button, not a submit, unless it says so — as Flux's did.
    'type' => 'button',
    // Makes it a link that looks like a button.
    'href' => null,
])

@php
    // Flux's names, so a page moving off Flux does not have to rename them.
    $variant = ['outline' => 'secondary', 'filled' => 'secondary', 'subtle' => 'ghost'][$variant] ?? $variant;
    $variant = in_array($variant, ['primary', 'secondary', 'danger', 'ghost'], true) ? $variant : 'secondary';

    // A button that runs a Livewire action is disabled while it runs, so a
    // second click cannot run it twice. A submit button in a wire:submit form
    // needs nothing: Livewire disables those itself.
    $click = collect($attributes->getAttributes())->first(fn ($value, $name) => str_starts_with($name, 'wire:click'));
    $guard = is_string($click) && ! collect($attributes->getAttributes())->keys()->contains(fn ($name) => str_starts_with($name, 'wire:loading'));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['dc-btn', "dc-btn-{$variant}", 'dc-btn-sm' => $size === 'sm']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['dc-btn', "dc-btn-{$variant}", 'dc-btn-sm' => $size === 'sm']) }}
            @if ($guard) wire:loading.attr="disabled" wire:target="{{ $click }}" @endif>{{ $slot }}</button>
@endif
