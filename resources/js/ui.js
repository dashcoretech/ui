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
