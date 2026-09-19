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

**JS** — in `resources/js/app.js`:

```js
import '../../vendor/dashcore/ui/resources/js/ui.js';
```

The drawer, folding sections and the switch work without it. The theme
toggle and theme choice, Show/Hide on a password, and the modal's events
need it.

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

An app whose sign-out is a Livewire action, with no `logout` route to post
to (Breeze's Livewire stack), puts the button in a small component of its
own and renders that in the footer:

```blade
{{-- resources/views/livewire/sign-out.blade.php, a Volt component --}}
<?php
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<button type="button" wire:click="logout" class="dc-nav-item">Sign out</button>
```

**Theme.** The tokens follow the OS unless `<html>` carries `dark` or `light`
(or `data-theme`). For a switch, put `<x-dashcore::theme-script />` in `<head>`
before the stylesheets and `<x-dashcore::theme-toggle />` where the switch
goes. `<x-dashcore::theme-choice />` offers Light, Dark and System instead —
for an Appearance page, or the foot of the sidebar — and shares the
theme-script's key and default; System removes the stored key, as Flux's
appearance setting did. An app that already stored a choice passes its old
key — `<x-dashcore::theme-script storage-key="app-theme" default="dark" />` —
so nobody's preference is lost in the move. An app whose framework already
owns the class (Flux's appearance setting) keeps that and skips all three.

`ui.css` also ships Tailwind's `dark` variant, matched to the tokens: a
`dark` class or `data-theme="dark"`, or the OS in dark mode unless `light` is
set. So an app's own `dark:` utilities agree with the tokens in every mode,
System included. An app that declares its own `@custom-variant dark` after
the import keeps its own, since the last declaration wins; delete it unless
something else (Flux) owns the class.

Livewire apps pass `navigate` so the menu's links carry `wire:navigate`.
`wire:navigate` copies the next page's `<html>` attributes over the live ones,
which would strip the theme class; `ui.js` puts it back inside the swap, so
import it in any Livewire app that has a theme switch.

**Messages.** `<x-dashcore::flash />` shows `session('status')` and
`session('success')` as success, `session('warning')` and `session('error')`
in their own tones, and the validation errors. `:errors="false"` leaves the
errors to the form, for pages whose fields already say what is wrong. A
status that is a code rather than a sentence — Fortify's
`verification-link-sent` — is shown only if the app has a translation for it;
otherwise the page that flashed it words it itself.

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
  them by their own attributes, and any `[role=switch]` or radio. Its avatars
  and pill badges do not.
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
| `icon` | entry | an SVG path `d` for a 24×24, 1.5-stroke line icon, or a list of them for a multi-path icon |
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
when given, or by `active` when the app says outright. When the prefix rule
matches more than one entry, only the most specific lights: on `leads.board`,
a `leads.board` entry and not `leads` beside it; on `pto.my`, `pto.my` and not
`pto.index`. `match` and `active` are the app speaking, and are left as said.
An entry whose named route does not exist is dropped, not thrown on.

## Components

All under `dashcore::`, on the tokens, and working in plain Blade and inside
Livewire. Anything not listed as a prop — `class`, `name`, `wire:model` and its
modifiers, `wire:click`, `required` — lands on the element that matters: the
`<input>`, not a wrapper.

**Forms.** Each control takes `label`, `hint` and `error`, and finds its own
error in the shared bag by its `wire:model` name or its `name`. `error`
overrides that: a string says something else, `false` says nothing.

```blade
<x-dashcore::input label="Email" type="email" wire:model.live.debounce.300ms="form.email" />
<x-dashcore::input label="Password" type="password" name="password" viewable />
<x-dashcore::select label="Role" wire:model="role" placeholder="Choose a role">
    <option value="admin">Admin</option>
</x-dashcore::select>
<x-dashcore::textarea label="Notes" name="notes" rows="6">{{ old('notes') }}</x-dashcore::textarea>
<x-dashcore::checkbox label="Remember me" name="remember" />
<x-dashcore::switch label="Email me a digest" wire:model.live="digest" />
<x-dashcore::otp wire:model="code" />
```

| Component | Props | Notes |
|---|---|---|
| `field` | `label`, `hint`, `error`, `for`, `name` | the label/hint/error furniture, around a control of the app's own |
| `input` | `type`, `viewable` | `viewable` adds Show/Hide to a password |
| `textarea`, `select` | `select`: `placeholder` | options are the slot |
| `checkbox`, `switch` | | the label sits beside it; the slot works as the label when it needs a link |
| `otp` | `length` (6), `label` | one digits-only field the OS can fill, not six boxes |

**Actions.**

```blade
<x-dashcore::button variant="primary" type="submit">Save changes</x-dashcore::button>
<x-dashcore::button href="{{ route('leads.index') }}" wire:navigate>Back to leads</x-dashcore::button>
<x-dashcore::button variant="danger" wire:click="archive({{ $lead->id }})">Archive</x-dashcore::button>
```

`variant` is `secondary` (the default), `primary`, `danger` or `ghost`;
`size="sm"`; `type` defaults to `button`, as Flux's did; `href` makes it an
`<a>`. A button with `wire:click` disables itself while that action runs,
unless it carries its own `wire:loading`. `<x-dashcore::link>` is a link in
running text.

**The modal** is a native `<dialog>`. A button opens it by name, with no
script; Escape and a click on the backdrop close it.

```blade
<x-dashcore::button variant="danger" commandfor="delete-lead" command="show-modal">Delete</x-dashcore::button>

<x-dashcore::modal name="delete-lead" title="Delete this lead?">
    <p class="text-sm text-dc-ink-muted">It goes for good.</p>
    <x-slot:footer>
        <x-dashcore::button commandfor="delete-lead" command="close">Cancel</x-dashcore::button>
        <x-dashcore::button variant="danger" wire:click="delete">Delete</x-dashcore::button>
    </x-slot:footer>
</x-dashcore::modal>
```

From Livewire, either bind it — `<x-dashcore::modal name="remove" wire:model="showRemoveModal">`,
open while the property is true and setting it false when it closes (this
half is Alpine, which Livewire brings) — or dispatch
`$this->dispatch('modal-show', name: 'remove')` and `modal-close`. Other props:
`open` (open as the page arrives — a form in it came back with errors),
`width` (`sm` to `2xl`, default `md`), `:dismissible="false"` (the backdrop
no longer closes it; Escape still does). `ui.js` supplies `commandfor` and
backdrop-click for browsers that have not shipped them.

**Tabs** — and any in-page list of links — are a `<nav>` with its own label,
inside `<main>`. The current tab is ink, found by path unless `current` says;
inside a Livewire component that re-renders, say.

```blade
<x-dashcore::tabs label="Settings">
    <x-dashcore::tab :href="route('profile.edit')" wire:navigate>Profile</x-dashcore::tab>
    <x-dashcore::tab :href="route('security.edit')" :current="request()->routeIs('security.*')">Security</x-dashcore::tab>
</x-dashcore::tabs>
```

**The rest.**

| Component | Props | Is |
|---|---|---|
| `tag` | `tone`: `success`, `warning`, `danger`; neutral otherwise | a status tag, not a pill. `color` takes Flux's badge colours (`green`, `amber`, `red`…) |
| `callout` | `tone` as the tag, `heading` | a note the page needs you to read, in the flash's treatment |
| `spinner` | `label` ("Loading") | a real circle, which the clamp leaves alone |
| `avatar` | `name`, `src`, `size` (`sm`, `md`, `lg`) | initials, or the photo; square at the fleet radius |
| `guest` | `product`, `home`, `title`, `subtitle`, `navigate`; a `footer` slot | the frame of a signed-out page: the wordmark and one panel |

There is no info colour: neutral is the informational tone.

For markup that does not use the components, the same looks are classes:
`dc-btn` with `dc-btn-primary`, `-secondary`, `-danger`, `-ghost` or `-sm`;
`dc-input`; `dc-panel`; `dc-tag` with `dc-tag-success`, `-warning`,
`-danger`; `dc-switch` on a checkbox with `role="switch"`.

## Proving an app conforms

A package test can prove the shell renders; only the app can prove it still
uses it. The check fails a page with no shell, and a page with a second
unlabelled `<nav>` or a second one labelled "Main" beside it. A `<nav>` with
its own label — pagination, a guide's contents, tabs — is fine. Each app carries one test against a real authenticated page:

```php
// Pest
use App\Models\User;
use Dashcore\Ui\Testing\Shell;

it('is drawn by the fleet shell', function () {
    $user = User::factory()->create();

    Shell::assertConforms($this->actingAs($user)->get('/dashboard')->getContent());
});
```

```php
// PHPUnit
use App\Models\User;
use Dashcore\Ui\Testing\Shell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellConformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_is_drawn_by_the_fleet_shell(): void
    {
        $user = User::factory()->create();

        Shell::assertConforms($this->actingAs($user)->get('/dashboard')->getContent());
    }
}
```

`Shell::of($html)` also reads the menu back — `groups()`, `links()`,
`linksUnder()`, `active()`, `version()` — for an app's own navigation tests.
Signed-out pages drawn by `<x-dashcore::guest>` have no menu to fork, and the
check is not for them.

## Migrating an app

"Put <app> on dashcore/ui" means the frame, the theme, the fonts and the menu,
in one change. Moving each page's contents onto the `dc-*` tokens and the
components is a follow-up, page by page; a first pass that also restyles
every page is too large to review. Top to bottom:

1. **Install.** The two composer lines, the CSS import and `@source`, the
   `ui.js` import, and `<x-dashcore::fonts />` in `<head>` — see Installing.
   Remove any `--font-sans` from the app's `@theme`.
2. **The layout.** Replace the app's sidebar, top bar and mobile menu with
   `<x-dashcore::shell>`, in every authenticated layout (controller pages and
   Livewire's own layout both). Pass `navigate` in a Livewire app.
3. **The product name.** `product` is the name beside "Dashcore" in the
   wordmark, as people say it: `product="HR"`, `product="Sales"`. Not the
   repository name, and not "Dashcore HR" (the brand is already there).
4. **The menu.** One class builds it (`App\Support\AppMenu::for($user)`), and
   every layout renders that one, gated by the same checks as the routes.
   - Headings become sections; a long, less-used group takes `collapsed`.
   - An entry fronting routes not named after it takes `match`
     (`'match' => ['timesheets.*', 'my-time']`); one whose rule is not a
     pattern takes `active` (`'active' => request()->routeIs('settings.*')`).
   - Sibling entries under one prefix (`leads` and `leads.board`) need
     nothing: the most specific lights.
   - A contextual entry — shown only on some pages, like "My timesheet" beside
     the timer — is added to the array conditionally. The shell has no
     second-level menu; a sub-section's pages get `<x-dashcore::tabs>`.
   - Icons are heroicons' outline paths: a string, or a list for several.
5. **The top bar.** The shell has none, deliberately. Page actions go in
   `<x-dashcore::page-header>`'s `actions` slot. Notifications become a menu
   entry with a `badge`. Search becomes a page (and a menu entry), or a field
   in the header of the pages it searches. The user menu becomes the footer.
6. **The footer.** `<x-dashcore::account>` with profile, the theme control and
   sign out. A Livewire-only sign-out is a small component (see above).
7. **The theme.** The class moves to `<html>`: the tokens read `:root`, so a
   `dark` class on `<body>` does nothing for them. Put
   `<x-dashcore::theme-script />` first in `<head>`. Keep a saved choice by
   passing its key: an app off Flux uses
   `storage-key="flux.appearance"` — Flux stored `light` or `dark` there and
   removed the key for System, which is exactly how the theme-script and
   theme-choice read it. Replace an Appearance page's own control with
   `<x-dashcore::theme-choice />`. Delete the app's `@custom-variant dark`
   unless Flux stays.
8. **Horizontal overflow.** `overflow-x: hidden` on `html` or `body` makes
   that element a scroll container, and the shell's sticky bar below `lg`
   then sticks to a box that never scrolls. Use `overflow-x: clip`.
9. **Pages that draw their own container.** A page whose view already wraps
   itself in `max-w-* mx-auto px-*` (or is full-bleed: a board, a map) gets a
   second container inside the shell's. Pass `:contained="false"` from its
   layout, or remove the page's own wrapper.
10. **Messages.** `<x-dashcore::flash />` under the page header. It reads
    `status` and `success` both; pass `:errors="false"` if the forms show
    their errors beside the fields.
11. **The conformance test**, in the app's own style (above).
12. **Run the suite.** An app's feature tests render the layout, which calls
    `@vite`, which reads `public/build/manifest.json`. Run `npm run build`
    first, or every page test fails on the missing manifest.

## Dropping Flux

Everything Flux gave a page has a counterpart here, so "and drop Flux" is a
swap rather than a rebuild.

**The trap first.** `@fluxScripts` forces Livewire to inject its assets on
every page. Without it, Livewire — and Alpine with it — loads only on pages
that render a Livewire component, and every `x-data` on a plain controller
page stops working with no error. Add both directives to every layout:

```blade
<head>
    ...
    @livewireStyles
</head>
<body>
    ...
    @livewireScripts
</body>
```

Then:

- `composer remove livewire/flux` (and `livewire/flux-pro`), and remove
  `@fluxAppearance`, `@fluxScripts`, Flux's CSS import and its
  `@custom-variant dark` line from `app.css`.
- `<x-dashcore::theme-script storage-key="flux.appearance" />` keeps
  everyone's saved theme (step 7 above).
- `$this->modal('x')->show()` becomes `$this->dispatch('modal-show', name: 'x')`;
  `->close()` is `modal-close`. `Flux::toast()` has no counterpart: flash a
  message and show it with `<x-dashcore::flash />`.
- Sign-in, registration and password pages move to `<x-dashcore::guest>`.

| Flux | dashcore/ui |
|---|---|
| `flux:sidebar`, `flux:header`, `flux:navlist` (the app menu) | `<x-dashcore::shell>` and the menu array |
| `flux:sidebar.profile`, the user dropdown | `<x-dashcore::account>` in the footer |
| `flux:main` | nothing — the shell draws `<main>` |
| `flux:navbar`, `flux:navlist` inside a page | `<x-dashcore::tabs>` and `<x-dashcore::tab>` |
| `flux:button` | `<x-dashcore::button>` — `outline`, `filled` and `subtle` are understood |
| `flux:input` | `<x-dashcore::input>` — `description` is `hint`, `viewable` is the same |
| `flux:textarea`, `flux:select`, `flux:select.option` | `<x-dashcore::textarea>`, `<x-dashcore::select>`, `<option>` |
| `flux:checkbox`, `flux:switch` | `<x-dashcore::checkbox>`, `<x-dashcore::switch>` |
| `flux:field`, `flux:label`, `flux:description`, `flux:error` | `<x-dashcore::field>` — `label`, `hint`, `error` |
| `flux:otp` | `<x-dashcore::otp>` |
| `flux:modal` (`wire:model.self`), `flux:modal.trigger`, `flux:modal.close` | `<x-dashcore::modal>` (`wire:model`); a button with `commandfor` and `command="show-modal"` or `"close"` |
| `flux:link` | `<x-dashcore::link>` |
| `flux:callout` | `<x-dashcore::callout>` — `variant` is `tone` |
| `flux:badge` | `<x-dashcore::tag>` — `color` is understood |
| `flux:avatar` | `<x-dashcore::avatar>` |
| `flux:icon.loading` | `<x-dashcore::spinner>` |
| `flux:heading`, `flux:subheading`, `flux:text` | `<h2 class="font-display text-xl font-medium">`, `<p class="text-sm text-dc-ink-muted">` |
| `flux:separator` | `<hr class="border-dc-border">` |
| `flux:dropdown`, `flux:menu` | no counterpart: a `<details>`, or Alpine, styled with the tokens |
| `flux:table` | a plain `<table>` on DESIGN.md's table rules |
| `x-layouts.auth` | `<x-dashcore::guest>` |

## Versioning

The shell renders `data-dc-ui="<version>"`, so any page says which release
drew it. A design change is finished when every app is on its release.
