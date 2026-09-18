// The shell's drawer is a checkbox the sidebar is a peer of, so it opens and
// closes without any of this. The one thing native markup will not do is
// close on Escape, and hand the keyboard back to the control that opened it.
//
// Imported from each app's own app.js:
//
//     import '../../vendor/dashcore/ui/resources/js/ui.js';
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    const drawer = document.querySelector('input[data-dc-drawer]');

    if (drawer?.checked) {
        drawer.checked = false;
        drawer.focus();
    }
});

// The theme toggle. The saved choice is applied before paint by
// <x-dashcore::theme-script>; this flips it, stores it under the key that
// script recorded, and keeps each toggle's label naming where it will go.
const root = document.documentElement;

// Read once, at load. wire:navigate copies the next page's <html> attributes
// over the live ones — which strips both the theme class and these data
// attributes — so they are captured here and re-applied after every swap.
const themeKey = root.dataset.dcThemeKey || 'dc-theme';
const themeDefault = root.dataset.dcThemeDefault || 'system';

const stored = () => {
    try {
        return localStorage.getItem(themeKey);
    } catch (e) {
        return null;
    }
};

const applyTheme = () => {
    const theme = stored() || themeDefault;

    root.dataset.dcThemeKey = themeKey;
    root.dataset.dcThemeDefault = themeDefault;

    if (theme === 'dark' || theme === 'light') {
        root.classList.remove('dark', 'light');
        root.classList.add(theme);

        if (root.hasAttribute('data-theme')) root.dataset.theme = theme;
    }
};

const isDark = () => {
    if (root.classList.contains('dark')) return true;
    if (root.classList.contains('light')) return false;

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const labelToggles = () => {
    document.querySelectorAll('[data-dc-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(isDark()));

        const label = button.querySelector('[data-dc-theme-label]');

        if (label) label.textContent = isDark() ? 'Light mode' : 'Dark mode';
    });
};

document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-dc-theme-toggle]')) return;

    const next = isDark() ? 'light' : 'dark';

    root.classList.remove('dark', 'light');
    root.classList.add(next);

    if (root.hasAttribute('data-theme')) root.dataset.theme = next;

    try {
        localStorage.setItem(themeKey, next);
    } catch (e) {}

    labelToggles();
});

document.addEventListener('DOMContentLoaded', labelToggles);

// Livewire 3.5+ lets a listener run inside the swap, before paint, so a dark
// page never flashes light between two pages. `navigated` is the fallback for
// older Livewire, and relabels the toggles either way.
document.addEventListener('livewire:navigating', (event) => {
    event.detail?.onSwap?.(applyTheme);
});

document.addEventListener('livewire:navigated', () => {
    applyTheme();
    labelToggles();
});
