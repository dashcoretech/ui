@props(['title', 'subtitle' => null])

{{-- The page's title in the display serif, its one-line purpose under it, and
     the page's actions to the right. The title is the one serif on most
     screens; that is what makes it read as the page's name. --}}
<div {{ $attributes->class('mb-8 flex flex-wrap items-end justify-between gap-4') }} data-dc-page-header>
    <div>
        <h1 class="dc-page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 text-sm text-dc-ink-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
