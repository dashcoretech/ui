<a href="{{ $item['href'] }}" {{ $wire }} class="dc-nav-item" @if ($item['active']) aria-current="page" @endif>
    @if ($item['icon'])
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="{{ $item['icon'] }}" />
        </svg>
    @endif
    {{ $item['label'] }}
</a>
