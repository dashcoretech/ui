@props([
    'name' => '',
    // A photo, when there is one; the initials otherwise.
    'src' => null,
    // sm, md or lg.
    'size' => 'md',
])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('') ?: '?';

    $sizes = ['sm' => 'h-6 w-6 text-[11px]', 'md' => 'h-8 w-8 text-xs', 'lg' => 'h-10 w-10 text-sm'];
@endphp

{{-- Square at the fleet's radius, like everything else: a round avatar is
     one of the pills the clamp exists for. --}}
<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center overflow-hidden rounded-sm border border-dc-border bg-dc-sunken font-medium text-dc-ink-muted', $sizes[$size] ?? $sizes['md']]) }} data-dc-avatar>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="h-full w-full object-cover">
    @else
        <span aria-hidden="true">{{ $initials }}</span>
        <span class="sr-only">{{ $name }}</span>
    @endif
</span>
