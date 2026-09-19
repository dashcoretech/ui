{{-- A link in running text: ink, underlined, never the accent (the accent is
     the page's one primary action). --}}
<a {{ $attributes->class('text-dc-ink underline decoration-dc-border-strong underline-offset-2 transition-colors duration-150 hover:decoration-dc-ink') }}>{{ $slot }}</a>
