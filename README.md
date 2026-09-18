# dashcore/ui

The Dashcore fleet's shared look: design tokens, the application shell, and
the rules that govern both. One package, so every app looks like one product
and a design change is a release rather than an edit in every repository.

**[DESIGN.md](DESIGN.md) is the design guide.** This file is only how to
install it.

## Installing

The repository is public, like `dashcore/bridge`: nothing secret ships in it,
and a build host needs no credentials to install it.

```bash
composer config repositories.dashcore-ui vcs https://github.com/dashcoretech/ui
composer require dashcore/ui:^0.1
```

**CSS** — in `resources/css/app.css`, directly after Tailwind:

```css
@import 'tailwindcss';
@import '../../vendor/dashcore/ui/resources/css/ui.css';
@source '../../vendor/dashcore/ui/resources/views';
```

The `@source` line is not optional: without it Tailwind never scans the shell's
views and the sidebar renders unstyled, with no error anywhere. Remove any
`--font-sans` the app sets in its own `@theme`; it would override the fleet's.

**JS** — in `resources/js/app.js` (optional; it adds Escape-to-close to the
drawer, which works without it):

```js
import '../../vendor/dashcore/ui/resources/js/ui.js';
```

**Layout** — fonts in `<head>`, the shell in `<body>`:

```blade
<head>
    ...
    <x-dashcore::fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-dashcore::shell :menu="$menu" product="Finance" :home="route('dashboard')">
        <x-dashcore::page-header :title="$title" />
        <x-dashcore::flash />
        {{ $slot }}

        <x-slot:footer>
            {{-- sign out, the signed-in user — the foot of the sidebar --}}
        </x-slot:footer>
    </x-dashcore::shell>
</body>
```

The foot of the sidebar is the signed-in person and what they can do about
their session:

```blade
<x-slot:footer>
    <x-dashcore::account :name="$user->name" :email="$user->email">
        <a href="{{ route('profile') }}" class="dc-nav-item">Profile</a>
        <x-dashcore::theme-toggle />
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dc-nav-item">Sign out</button>
        </form>
    </x-dashcore::account>
</x-slot:footer>
```

**Theme.** The tokens follow the OS unless `<html>` carries `dark` or `light`
(or `data-theme`). For a switch, put `<x-dashcore::theme-script />` in `<head>`
before the stylesheets and `<x-dashcore::theme-toggle />` where the switch
goes. An app that already stored a choice passes its old key —
`<x-dashcore::theme-script storage-key="app-theme" default="dark" />` — so
nobody's preference is lost in the move. An app whose framework already owns
the class (Flux's appearance setting) keeps that and skips both.

Livewire apps pass `navigate` so the menu's links carry `wire:navigate`.
`wire:navigate` copies the next page's `<html>` attributes over the live ones,
which would strip the theme class; `ui.js` puts it back inside the swap, so
import it in any Livewire app that has a theme switch.

**Messages.** `<x-dashcore::flash />` shows `session('status')` as success,
`session('warning')` and `session('error')` in their own tones, and the
validation errors.

### With Flux

- Do not wrap pages in `<flux:main>` inside the shell. The shell already draws
  `<main>`, and Flux's `*:has(>[data-flux-main])` turns the shell's own
  container into a page-sized grid.
- Flux's accent (`--color-accent*`) is pointed at the fleet accent by the
  package. Remove any `--color-accent` the app pins in its own CSS, or the app
  has two accents.
- Flux's appearance setting owns the `dark` class, and keeps it. It never adds
  `light`, though, so someone on a dark OS who chooses Light would get dark
  tokens around light Flux components. Mirror the choice onto `<html>` in the
  app's head until Flux does:

  ```html
  <script>
      (() => {
          const a = localStorage.getItem('flux.appearance');
          document.documentElement.classList.toggle('light', a === 'light');
      })();
  </script>
  ```
- Flux's radios and switches keep their round shape; the radius clamp exempts
  them by their own attributes. Its avatars and pill badges do not.
- Use `<x-dashcore::account>` in the footer rather than `<flux:sidebar.profile>`,
  which brings its own zinc palette.
A page that draws its own full-bleed layout passes `:contained="false"`.

## The menu

The app owns it; the shell owns how it looks.

```php
$menu = [
    ['label' => 'Overview', 'route' => 'dashboard'],
    ['label' => 'Money', 'items' => [
        'accounts.index' => 'Accounts',                  // route => label
        ['label' => 'Runway', 'route' => 'runway', 'icon' => 'M3 3v18h18'],
    ]],
    ['label' => 'Help', 'href' => '/help'],
];
```

Beyond label and destination, an entry or a section can carry:

| Key | On | Does |
|---|---|---|
| `icon` | entry | one SVG path `d` for a 24×24, 1.5-stroke line icon |
| `badge` | entry | a muted count beside the label; `0` and `null` show nothing |
| `hint` | entry | a second, muted line — a mailbox's purpose |
| `tone` | entry | `success`, `warning` or `danger` for the hint, when it is a problem to fix |
| `active` / `match` | entry | say outright which entry is current, or by route pattern |
| `collapsed` | section | folds the section; it opens by itself on a page inside it |
| `link` | section | one quiet link beside the heading — `['label' => 'Manage', 'route' => …]` |

Consecutive top-level entries render as one list; a heading starts a new
section.

An entry is current by route-name prefix (`services.show` lights
`services.index`), by path for an `href`, by `match` (a route pattern or list)
when given, or by `active` when the app says outright. An entry whose named
route does not exist is dropped, not thrown on.

## Proving an app conforms

A package test can prove the shell renders; only the app can prove it still
uses it. The check fails a page with no shell, and a page with a second
unlabelled `<nav>` or a second one labelled "Main" beside it. A `<nav>` with
its own label — pagination, a guide's contents — is fine. Each app carries one test against a real authenticated page:

```php
use Dashcore\Ui\Testing\Shell;

it('is drawn by the fleet shell', function () {
    Shell::assertConforms($this->actingAs($user)->get('/dashboard')->getContent());
});
```

`Shell::of($html)` also reads the menu back — `groups()`, `links()`,
`linksUnder()`, `active()`, `version()` — for an app's own navigation tests.

## Versioning

The shell renders `data-dc-ui="<version>"`, so any page says which release
drew it. A design change is finished when every app is on its release.
