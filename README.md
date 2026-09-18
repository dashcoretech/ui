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

Livewire apps pass `navigate` so the menu's links carry `wire:navigate`.
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

An entry is current by route-name prefix (`services.show` lights
`services.index`), by path for an `href`, by `match` (a route pattern or list)
when given, or by `active` when the app says outright. An entry whose named
route does not exist is dropped, not thrown on.

## Proving an app conforms

A package test can prove the shell renders; only the app can prove it still
uses it. Each app carries one test against a real authenticated page:

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
