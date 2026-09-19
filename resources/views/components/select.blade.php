@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    // An empty first option, for a choice nobody has made yet.
    'placeholder' => null,
])

@php
    $key = \Dashcore\Ui\Field::key($attributes);
    $id = \Dashcore\Ui\Field::id($attributes, $key);
    $message = \Dashcore\Ui\Field::error($error, $errors ?? null, $key);
@endphp

{{-- The options are the slot, as plain <option>s. --}}
<x-dashcore::field :label="$label" :hint="$hint" :error="$message ?? false" :for="$id">
    <select id="{{ $id }}"
            {{ $attributes->except('id')->class('dc-input') }}
            @if ($message) aria-invalid="true" @endif
            @if ($message || $hint) aria-describedby="{{ $id }}-note" @endif>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>
</x-dashcore::field>
