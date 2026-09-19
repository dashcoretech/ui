@props([
    // The modal's id, and what opens it: a button with commandfor="<name>"
    // command="show-modal", or $this->dispatch('modal-show', name: '<name>').
    'name',
    'title' => null,
    // Open as the page arrives — a form in it that came back with errors.
    'open' => false,
    // sm, md, lg, xl or 2xl.
    'width' => 'md',
    // False keeps a click on the backdrop from closing it; Escape still does.
    'dismissible' => true,
])

@php
    // wire:model binds it to a Livewire property instead: open while it is
    // true, and set false when the modal closes by any route. That half is
    // Alpine, which Livewire always brings; nothing else here needs script.
    $model = collect($attributes->getAttributes())->first(fn ($value, $key) => $key === 'wire:model' || str_starts_with($key, 'wire:model.'));
    $live = $attributes->has('wire:model.live') ? 'true' : 'false';

    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'];
@endphp

{{-- A native <dialog>: the top layer, a focus trap, Escape and the backdrop
     all come from the browser. wire:ignore.self because the browser owns its
     open attribute, and a Livewire re-render would otherwise take it away. --}}
<dialog id="{{ $name }}" data-dc-modal="{{ $name }}" closedby="{{ $dismissible ? 'any' : 'closerequest' }}" wire:ignore.self
        @if ($open) data-dc-open @endif
        @if ($title) aria-labelledby="{{ $name }}-title" @endif
        @if (is_string($model))
            x-data
            x-effect="$wire.$get(@js($model)) ? $el.open || $el.showModal() : $el.open && $el.close()"
            x-on:close="$wire.$get(@js($model)) && $wire.$set(@js($model), false, {{ $live }})"
        @endif
        {{ $attributes->whereDoesntStartWith('wire:model')->class(['m-auto w-[calc(100%-2rem)] rounded-md border border-dc-border bg-dc-surface p-6 text-dc-ink backdrop:bg-dc-scrim', $widths[$width] ?? 'max-w-md']) }}>
    <button type="button" commandfor="{{ $name }}" command="close" aria-label="Close"
            class="absolute right-3 top-3 p-1.5 text-dc-ink-muted transition-colors duration-150 hover:text-dc-ink">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
            <path d="m5 5 10 10M15 5 5 15" />
        </svg>
    </button>

    @if ($title)
        <h2 id="{{ $name }}-title" class="mb-4 pr-8 font-display text-xl font-medium">{{ $title }}</h2>
    @endif

    {{ $slot }}

    @isset($footer)
        <div class="mt-6 flex flex-wrap justify-end gap-2">{{ $footer }}</div>
    @endisset
</dialog>
