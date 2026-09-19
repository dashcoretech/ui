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

{{-- A checkbox with role="switch", drawn as a track by .dc-switch: it submits
     and binds (wire:model.live) exactly as a checkbox does, and needs no
     script. For a setting that takes effect at once; in a form with a Save
     button, a checkbox says it better. --}}
<x-dashcore::field :error="$message ?? false" :hint="$hint" :for="$id">
    <label class="inline-flex items-center gap-3 text-sm text-dc-ink">
        <input type="checkbox" role="switch" id="{{ $id }}"
               {{ $attributes->except('id')->class('dc-switch') }}
               @if ($message) aria-invalid="true" @endif
               @if ($message || $hint) aria-describedby="{{ $id }}-note" @endif>
        <span>{{ $label ?? $slot }}</span>
    </label>
</x-dashcore::field>
