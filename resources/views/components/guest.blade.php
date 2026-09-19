@props([
    // The wordmark, as the shell draws it.
    'brand' => 'Dashcore',
    'product' => null,
    'home' => url('/'),
    'title' => null,
    'subtitle' => null,
    // Livewire apps pass true so the wordmark carries wire:navigate.
    'navigate' => false,
])

@php($version = \Dashcore\Ui\Version::installed())

{{-- The frame of a signed-out page — sign in, reset a password, confirm an
     email: the wordmark, and one panel under it. No menu: there is nowhere
     to go until you are in. Goes in <body>, as the shell does. --}}
<div {{ $attributes->class('flex min-h-dvh flex-col items-center justify-center bg-dc-bg px-4 py-12 font-sans text-dc-ink antialiased') }}
     data-dc-guest @if ($version) data-dc-ui="{{ $version }}" @endif>
    <a href="{{ $home }}" @if ($navigate) wire:navigate @endif class="font-display text-lg font-medium tracking-tight text-dc-ink">
        {{ $brand }}@if ($product) <span class="text-dc-ink-muted">{{ $product }}</span>@endif
    </a>

    <main class="mt-8 w-full max-w-sm rounded-md border border-dc-border bg-dc-surface p-8">
        @if ($title)
            <div class="mb-6">
                <h1 class="font-display text-xl font-medium">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1.5 text-sm text-dc-ink-muted">{{ $subtitle }}</p>
                @endif
            </div>
        @endif

        {{ $slot }}
    </main>

    @isset($footer)
        <div class="mt-6 text-center text-sm text-dc-ink-muted">{{ $footer }}</div>
    @endisset
</div>
