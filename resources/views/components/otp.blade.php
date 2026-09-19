@props([
    'label' => 'Authentication code',
    'length' => 6,
    'error' => null,
])

@php
    $key = \Dashcore\Ui\Field::key($attributes);
    $id = \Dashcore\Ui\Field::id($attributes, $key);
    $message = \Dashcore\Ui\Field::error($error, $errors ?? null, $key);
@endphp

{{-- A one-time code as one field, digits only, that the OS can fill from a
     text message or an authenticator app — not six boxes and six focus hops
     to type one number. The label is for screen readers; the page around it
     already says what the code is. --}}
<x-dashcore::field :error="$message ?? false" :for="$id">
    <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    <input type="text" id="{{ $id }}" inputmode="numeric" pattern="[0-9]*" maxlength="{{ $length }}" autocomplete="one-time-code"
           {{ $attributes->except('id')->class('dc-input dc-tabular text-center font-mono text-xl tracking-[0.5em]') }}
           @if ($message) aria-invalid="true" aria-describedby="{{ $id }}-note" @endif>
</x-dashcore::field>
