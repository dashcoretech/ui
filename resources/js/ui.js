// The package's few behaviours that markup cannot do alone: Escape on the
// drawer, the theme controls, Show/Hide on a password, and the modal's
// events and fallbacks. Everything else — the drawer, a folding section, a
// switch — works with no script at all.
//
// Imported from each app's own app.js:
//
//     import '../../vendor/dashcore/ui/resources/js/ui.js';
// The drawer is a checkbox the sidebar is a peer of. Escape closes it and
// hands the keyboard back to the control that opened it.
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    const drawer = document.querySelector('input[data-dc-drawer]');

    if (drawer?.checked) {
        drawer.checked = false;
        drawer.focus();
    }
});

// The theme controls. The saved choice is applied before paint by
// <x-dashcore::theme-script>; this changes it, stores it under the key that
// script names, and keeps each control's pressed state in step. The toggle
// flips between light and dark; <x-dashcore::theme-choice> also offers System.
const root = document.documentElement;

// The key and default come from the <x-dashcore::theme-script> tag on the
// current page, read fresh every time. wire:navigate copies the next page's
// <html> attributes over the live ones, so nothing stored there survives a
// swap, and a value captured once at load is wrong whenever the visit began on
// a page without the tag.
const themeConfig = () => {
    const tag = document.querySelector('script[data-dc-theme-key]');

    return {
        key: tag?.dataset.dcThemeKey || 'dc-theme',
        fallback: tag?.dataset.dcThemeDefault || 'system',
        present: tag !== null,
    };
};

const stored = (key) => {
    try {
        return localStorage.getItem(key);
    } catch (e) {
        return null;
    }
};

const applyTheme = () => {
    // A page with no theme-script has no switch to honour; leave it alone.
    if (!themeConfig().present) return;

    const theme = choice();

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

// What the person chose: 'light', 'dark' or 'system'.
const choice = () => {
    const { key, fallback } = themeConfig();
    const theme = stored(key) || fallback;

    return theme === 'dark' || theme === 'light' ? theme : 'system';
};

// The visible label and the current choice are drawn by CSS from the <html>
// class, so they are right before this script loads; this keeps the pressed
// state in step for assistive tech.
const labelToggles = () => {
    document.querySelectorAll('[data-dc-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', String(isDark()));
    });

    document.querySelectorAll('[data-dc-theme-set]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.dcThemeSet === choice()));
    });
};

// System clears the class, so the tokens follow the OS again, and removes the
// stored key — which is also how Flux's appearance setting stores it. Where
// the app's default is not System, a missing key would mean that default, so
// the word itself is stored; the theme-script reads it as "leave <html> bare".
const setTheme = (theme) => {
    const { key, fallback } = themeConfig();

    root.classList.remove('dark', 'light');

    if (theme !== 'system') root.classList.add(theme);

    if (root.hasAttribute('data-theme')) root.dataset.theme = theme;

    try {
        if (theme === 'system' && fallback === 'system') {
            localStorage.removeItem(key);
        } else {
            localStorage.setItem(key, theme);
        }
    } catch (e) {}

    labelToggles();
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-dc-theme-toggle]')) {
        setTheme(isDark() ? 'light' : 'dark');

        return;
    }

    const set = event.target.closest('[data-dc-theme-set]');

    if (set) setTheme(set.dataset.dcThemeSet);
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

// <x-dashcore::input viewable>: Show/Hide beside a password. A Livewire
// re-render puts both back to hidden together, as the server drew them.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-dc-reveal]');
    const input = button && document.getElementById(button.getAttribute('aria-controls'));

    if (!input) return;

    const shown = input.type === 'password';

    input.type = shown ? 'text' : 'password';
    button.textContent = shown ? 'Hide' : 'Show';
    button.setAttribute('aria-pressed', String(shown));
});

// The modal is a native <dialog>. A button with commandfor="<name>" and
// command="show-modal" (or "close") opens and closes it with no script at
// all, and closedby="any" lets a click on the backdrop close it; the two
// fallbacks below cover browsers that have not shipped those yet.
const modal = (name) => document.querySelector(`dialog[data-dc-modal="${CSS.escape(name)}"]`);

const show = (dialog) => {
    if (dialog && !dialog.open) dialog.showModal();
};

if (!('commandForElement' in HTMLButtonElement.prototype)) {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('button[commandfor]');
        const dialog = button && document.getElementById(button.getAttribute('commandfor'));

        if (!(dialog instanceof HTMLDialogElement)) return;

        const command = button.getAttribute('command');

        if (command === 'show-modal') show(dialog);
        if (command === 'close' || command === 'request-close') dialog.close();
    });
}

if (!('closedBy' in HTMLDialogElement.prototype)) {
    document.addEventListener('click', (event) => {
        const dialog = event.target;

        if (!(dialog instanceof HTMLDialogElement) || !dialog.matches('[data-dc-modal][closedby="any"]')) return;

        // A click on the dialog element itself, outside its box, is the backdrop.
        const box = dialog.getBoundingClientRect();
        const inside = event.clientX >= box.left && event.clientX <= box.right && event.clientY >= box.top && event.clientY <= box.bottom;

        if (!inside) dialog.close();
    });
}

// From a server action: $this->dispatch('modal-show', name: 'confirm-delete').
// The same event names Flux used, so a component that opened a Flux modal
// that way keeps working. Without a name, modal-close closes every one.
window.addEventListener('modal-show', (event) => show(modal(event.detail?.name ?? '')));

window.addEventListener('modal-close', (event) => {
    const name = event.detail?.name;

    document.querySelectorAll('dialog[data-dc-modal]').forEach((dialog) => {
        if (!name || dialog.dataset.dcModal === name) dialog.close();
    });
});

// A modal rendered with :open="true" — a form that came back with errors —
// opens as the page arrives.
const openRendered = () => document.querySelectorAll('dialog[data-dc-modal][data-dc-open]').forEach(show);

document.addEventListener('DOMContentLoaded', openRendered);
document.addEventListener('livewire:navigated', openRendered);
