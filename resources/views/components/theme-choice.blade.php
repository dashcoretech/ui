@props(['label' => 'Theme'])

{{-- Light, Dark or System, for an Appearance page or the foot of the sidebar;
     ui.js does the work, under the key and default the theme-script names.
     CSS marks the current choice from <html>, right from first paint. Needs
     <x-dashcore::theme-script /> in <head>, as the toggle does. --}}
<div role="group" aria-label="{{ $label }}" {{ $attributes->class('dc-theme-choice') }} data-dc-theme-choice>
    @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $text)
        <button type="button" data-dc-theme-set="{{ $value }}" aria-pressed="false">{{ $text }}</button>
    @endforeach
</div>
