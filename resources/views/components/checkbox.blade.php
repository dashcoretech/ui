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

{{-- The label beside the box, and the whole row is the click target. Ink when
     checked, not browser blue. The label is `label`, or the slot when it needs
     a link in it. --}}
<x-dashcore::field :error="$message ?? false" :hint="$hint" :for="$id">
    <label class="inline-flex items-start gap-2.5 text-sm text-dc-ink">
        <input type="checkbox" id="{{ $id }}"
               {{ $attributes->except('id')->class('mt-0.5 h-4 w-4 shrink-0 accent-dc-ink') }}
               @if ($message) aria-invalid="true" @endif
               @if ($message || $hint) aria-describedby="{{ $id }}-note" @endif>
        <span>{{ $label ?? $slot }}</span>
    </label>
</x-dashcore::field>
