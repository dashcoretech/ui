@props(['label' => 'Loading'])

{{-- One of the shapes that really is a circle, so dc-circle rather than
     rounded-full, which the fleet clamps to 4px. --}}
<span {{ $attributes->class('dc-circle inline-block h-4 w-4 shrink-0 animate-spin border-2 border-dc-border border-t-dc-ink-muted') }}
      role="status" aria-label="{{ $label }}"></span>
