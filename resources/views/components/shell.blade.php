@props([
    // The app's menu. See Dashcore\Ui\Menu for the shapes it accepts.
    'menu' => [],
    // The wordmark: the brand in ink, the product beside it in muted ink.
    'brand' => 'Dashcore',
    'product' => null,
    'home' => url('/'),
    // Livewire apps pass true so the menu's links carry wire:navigate.
    'navigate' => false,
    // False for a page that draws its own full-bleed layout inside <main>.
    'contained' => true,
])

@php
    $entries = \Dashcore\Ui\Menu::resolve($menu);
    $version = \Dashcore\Ui\Version::installed();
    $wire = $navigate ? 'wire:navigate' : '';
@endphp

{{--
    The fleet's shell. The sidebar is the whole menu at every width: pinned at
    lg, a drawer below it. The menu renders once, so there is no second copy
    for a link to be forgotten in.

    The drawer is a checkbox the sidebar and the backdrop are peers of; the
    menu button, the backdrop and the close button are all labels for it. It
    opens with JavaScript off, a page load (or a wire:navigate swap) resets it,
    and ui.js adds only Escape. The checkbox comes first because peer-checked:
    reaches later siblings only.
--}}
<div {{ $attributes->class('min-h-dvh bg-dc-bg font-sans text-dc-ink antialiased') }}
     data-dc-shell @if ($version) data-dc-ui="{{ $version }}" @endif>
    <input type="checkbox" id="dc-drawer" class="peer sr-only lg:hidden" data-dc-drawer aria-label="Menu">

    <div class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-dc-border bg-dc-surface px-4 lg:hidden">
        <a href="{{ $home }}" {{ $wire }} class="font-display text-[15px] font-medium tracking-tight text-dc-ink">
            {{ $brand }}@if ($product) <span class="text-dc-ink-muted">{{ $product }}</span>@endif
        </a>
        <label for="dc-drawer" aria-hidden="true" class="cursor-pointer p-1.5 text-dc-ink-muted transition-colors hover:text-dc-ink">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                <path d="M3 5.5h14M3 10h14M3 14.5h14" />
            </svg>
        </label>
    </div>

    <label for="dc-drawer" aria-hidden="true"
           class="fixed inset-0 z-40 hidden bg-black/40 peer-checked:block lg:peer-checked:hidden"></label>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col border-r border-dc-border bg-dc-surface transition-transform duration-150 peer-checked:translate-x-0 lg:translate-x-0"
           data-dc-sidebar>
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-dc-border px-5">
            <a href="{{ $home }}" {{ $wire }} class="font-display text-[15px] font-medium tracking-tight text-dc-ink">
                {{ $brand }}@if ($product) <span class="text-dc-ink-muted">{{ $product }}</span>@endif
            </a>
            <label for="dc-drawer" aria-hidden="true" class="cursor-pointer p-1 text-dc-ink-muted transition-colors hover:text-dc-ink lg:hidden">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
                    <path d="m5 5 10 10M15 5 5 15" />
                </svg>
            </label>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5" aria-label="Main" data-dc-nav>
            @foreach ($entries as $entry)
                @if (isset($entry['items']))
                    <div>
                        <p class="dc-label mb-1.5 px-3">{{ $entry['label'] }}</p>
                        <div class="space-y-0.5">
                            @foreach ($entry['items'] as $item)
                                @include('dashcore::partials.nav-item', ['item' => $item, 'wire' => $wire])
                            @endforeach
                        </div>
                    </div>
                @else
                    @include('dashcore::partials.nav-item', ['item' => $entry, 'wire' => $wire])
                @endif
            @endforeach
        </nav>

        @isset($footer)
            <div class="shrink-0 border-t border-dc-border p-3">
                {{ $footer }}
            </div>
        @endisset
    </aside>

    <div class="lg:pl-60">
        <main @class(['mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8' => $contained])>
            {{ $slot }}
        </main>
    </div>
</div>
