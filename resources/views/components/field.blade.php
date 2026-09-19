@props([
    'label' => null,
    'hint' => null,
    // A string to say something else, false to say nothing; otherwise the
    // shared error bag's first message for `name`.
    'error' => null,
    // The control's id, for the label to point at.
    'for' => null,
    // The field's key, for an app's own control inside the wrapper.
    'name' => null,
])

@php($message = \Dashcore\Ui\Field::error($error, $errors ?? null, $name))

{{-- A label above, the control, and one line under it: the error if there is
     one, the hint if not. The form components draw themselves inside this;
     an app wraps its own control in it to get the same furniture. --}}
<div {{ $attributes->class('grid gap-1.5') }} data-dc-field>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="dc-label">{{ $label }}</label>
    @endif

    {{ $slot }}

    @if ($message)
        <p @if ($for) id="{{ $for }}-note" @endif class="text-xs text-dc-danger" data-dc-error>{{ $message }}</p>
    @elseif ($hint)
        <p @if ($for) id="{{ $for }}-note" @endif class="text-xs text-dc-ink-muted" data-dc-hint>{{ $hint }}</p>
    @endif
</div>
