@props([
    // Where the choice is kept. An app moving onto the shell passes its old
    // key so nobody's saved preference is lost in the move.
    'storageKey' => 'dc-theme',
    // What an app shows before anyone chooses: 'system', 'light' or 'dark'.
    'default' => 'system',
])

{{-- In <head>, before any stylesheet paints: applies a saved choice as a
     dark/light class on <html>, which is what the tokens read. Inline and
     synchronous on purpose — run any later and a dark-mode user sees a white
     flash on every page load. --}}
<script>
    (() => {
        const root = document.documentElement;
        const key = @js($storageKey);
        let theme = null;

        root.dataset.dcThemeKey = key;
        root.dataset.dcThemeDefault = @js($default);

        try {
            theme = localStorage.getItem(key);
        } catch (e) {}

        theme = theme || @js($default);

        if (theme === 'dark' || theme === 'light') {
            root.classList.remove('dark', 'light');
            root.classList.add(theme);

            if (root.hasAttribute('data-theme')) root.dataset.theme = theme;
        }
    })();
</script>
