@props([
    // Its own name, so it is never mistaken for a second main menu.
    'label' => 'Sections',
])

{{-- Tabs, or any in-page list of links: a <nav> with its own label, inside
     <main>. The items are <x-dashcore::tab>s. --}}
<nav aria-label="{{ $label }}" {{ $attributes->class('mb-8 flex gap-6 overflow-x-auto border-b border-dc-border') }} data-dc-tabs>
    {{ $slot }}
</nav>
