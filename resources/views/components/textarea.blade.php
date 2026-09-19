@props([
    'label' => null,
    'hint' => null,
    'error' => null,
])

@php
    $key = \Dashcore\Ui\Field::key($attributes);
    $id = \Dashcore\Ui\Field::id($attributes, $key);
    $message = \Dashcore\Ui\Field::error($error, $errors ?? null, $key);
@endphp

<x-dashcore::field :label="$label" :hint="$hint" :error="$message ?? false" :for="$id">
    <textarea id="{{ $id }}"
              {{ $attributes->except('id')->merge(['rows' => 3])->class('dc-input') }}
              @if ($message) aria-invalid="true" @endif
              @if ($message || $hint) aria-describedby="{{ $id }}-note" @endif>{{ $slot }}</textarea>
</x-dashcore::field>
