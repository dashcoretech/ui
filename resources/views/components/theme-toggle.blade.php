{{-- Flips between light and dark and remembers it (ui.js does the work). The
     label names where it will go, not where it is. Needs
     <x-dashcore::theme-script /> in <head>, or the choice is forgotten on the
     next page load. --}}
<button type="button" {{ $attributes->class('dc-nav-item') }} data-dc-theme-toggle aria-pressed="false">
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
    </svg>
    <span data-dc-theme-label>Dark mode</span>
</button>
