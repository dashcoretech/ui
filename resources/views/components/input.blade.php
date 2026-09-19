@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'type' => 'text',
    // A password field with a Show/Hide button beside it (ui.js flips it).
    'viewable' => false,
])

@php
    $key = \Dashcore\Ui\Field::key($attributes);
    $id = \Dashcore\Ui\Field::id($attributes, $key);
    $message = \Dashcore\Ui\Field::error($error, $errors ?? null, $key);
@endphp

{{-- Everything else — name, wire:model and its modifiers, value, required,
     class — lands on the <input> itself. --}}
<x-dashcore::field :label="$label" :hint="$hint" :error="$message ?? false" :for="$id">
    @if ($viewable)
        <div class="relative">
    @endif

    <input type="{{ $type }}" id="{{ $id }}"
           {{ $attributes->except('id')->class(['dc-input', 'pr-16' => $viewable]) }}
           @if ($message) aria-invalid="true" @endif
           @if ($message || $hint) aria-describedby="{{ $id }}-note" @endif>

    @if ($viewable)
            <button type="button" class="absolute inset-y-0 right-0 px-3 text-xs text-dc-ink-muted transition-colors duration-150 hover:text-dc-ink"
                    aria-controls="{{ $id }}" aria-pressed="false" data-dc-reveal>Show</button>
        </div>
    @endif
</x-dashcore::field>
