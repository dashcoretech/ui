# The Dashcore design rules

Every app in the fleet follows these. They ship inside `dashcore/ui` so the
rules and the code that implements them version together: a rule change is a
release, and `composer update dashcore/ui` is how it reaches an app.

The base is the executive design system `ceo` was built on, extended with a
dark palette. Where this file and a copy of the older guide in an app
disagree, this file wins; delete the copy.

## The one-sentence brief

Quiet, heavy, warm-neutral, serif-forward, hairline-bordered, zero-gloss. If a
screen could be mistaken for a generic SaaS dashboard, it has failed the brief.

## Principles

**Restraint is the luxury signal.** Fewer colours, fewer weights, fewer
competing elements per screen. When in doubt, remove something.

**Borders over shadows.** Separate with a single hairline (`border-dc-border`),
never a drop shadow. Shadows read as "app"; borders read as furniture.

**Serif carries weight, sans carries information.** Page titles, section
titles and anything meant to feel authoritative use `font-display` (Fraunces).
Data, tables, labels and anything operational use `font-sans` (Inter). A serif
data table looks precious, not premium.

**Numbers are furniture.** Every table and every figure gets `dc-tabular`,
numeric columns are right-aligned, and rows are at least 44px tall.

**Colour is a tool, used once per screen.** `dc-accent` does exactly one job
per screen: the primary action, or the active state. Semantic colour is the
muted set below, never traffic-light bright.

**Sharp corners, always.** 4px is the ceiling anywhere — buttons, inputs,
cards, badges, modals. The package clamps every `rounded-*` utility,
`rounded-full` included, so this holds without anyone remembering it. A shape
that truly is a circle (a status dot, a spinner) uses `dc-circle`.

## Tokens

Use the token, never the value: `bg-dc-surface`, not `bg-white`. The value
lives in `resources/css/ui.css` and changes in one place. A token class is
correct in both colour schemes, so no view writes a `dark:` variant for one.

| Token | Light | Dark | Job |
|---|---|---|---|
| `dc-bg` | `#f5f2ec` | `#151412` | page background — warm, never pure white or black |
| `dc-surface` | `#ffffff` | `#1d1b18` | panels, the sidebar |
| `dc-sunken` | `#efebe2` | `#11100e` | inputs, recessed areas, dense-table stripes |
| `dc-ink` | `#1c1b19` | `#ece7dd` | primary text |
| `dc-ink-muted` | `#6b6560` | `#a39c90` | secondary text, captions, labels |
| `dc-ink-faint` | `#9b948a` | `#6f695f` | disabled, tertiary |
| `dc-border` | `#d8d2c7` | `#2e2b26` | the default hairline |
| `dc-border-strong` | `#b8af9f` | `#454039` | table header rule, secondary button, active input |
| `dc-accent` | `#4a4438` | `#d9cbb0` | **one per screen** — primary action or active state |
| `dc-accent-hover` | `#33301f` | `#e8dcc4` | hover on the accent: moves away from the page, never toward it |
| `dc-on-accent` | `#ffffff` | `#1c1b19` | text on an accent fill |
| `dc-metal` | `#8a7a5c` | `#9c8b6a` | decorative icons and rules — never text |
| `dc-success` / `-bg` | `#5b6e4f` / `#eef0e8` | `#9db08c` / `#1f241b` | muted olive |
| `dc-warning` / `-bg` | `#9c7a3d` / `#f5eee0` | `#d1ae6e` / `#2a2418` | muted ochre |
| `dc-danger` / `-bg` | `#8c4a3d` / `#f3e7e2` | `#cf8b7c` / `#2b1d19` | muted brick |

**Dark mode** follows the OS by default. An app that offers a switch sets
`class="dark"` / `class="light"` or `data-theme="dark|light"` on `<html>`; all
three are honoured, and an explicit light choice beats the OS.

## Type

Load the faces with `<x-dashcore::fonts />` in `<head>`. Use these sizes only.

| Role | Size | Weight | Face | Class |
|---|---|---|---|---|
| Page title | 28px | 500 | Fraunces | `dc-page-title` (or `<x-dashcore::page-header>`) |
| Section title | 20px | 500 | Fraunces | `font-display text-xl font-medium` |
| Panel title | 16px | 500 | Fraunces | `font-display text-base font-medium` |
| Body | 14px | 400 | Inter | `text-sm` |
| Caption | 12px | 400 | Inter | `text-xs` |
| Eyebrow label | 11px | 500, tracked, uppercase | Inter | `dc-label` |
| Table data | 13px | 400, tabular | Inter | `text-[13px] dc-tabular` |

Inter at 400 and 500 only. Fraunces at 400–600, never 700.

## Spacing

An 8px unit. Panel padding `p-6`. Between major sections `gap-12` — more than
feels natural at first. Form fields `gap-4`. Table rows at least 44px.

## The shell

`<x-dashcore::shell>` is the frame of every authenticated page, and it is not
something an app restyles. It owns:

- **Where the menu is.** A left sidebar, `w-60`, pinned from `lg`; below `lg`,
  a 56px bar and the same sidebar as a drawer. The menu renders once.
- **How "you are here" looks.** The accent as text plus a 2px rule on the left
  edge, driven by `aria-current="page"`. Never a filled pill.
- **The wordmark.** Brand in ink, product in muted ink, in the display face.

The app owns what goes *in* it: the menu (labels, grouping, routes), the
product name, the sidebar footer, and everything inside `<main>`. If an app
needs the frame itself to change, that is a change to this package, reviewed
once and released to everyone. An override in one app is a fork nobody sees.

## Components

**Buttons.** Primary: `bg-dc-accent text-dc-on-accent hover:bg-dc-accent-hover`,
`rounded-sm`, no shadow. Secondary: transparent, `border border-dc-border-strong
text-dc-ink`. Never a gradient; hover shifts colour, never adds a glow.

**Panels.** `bg-dc-surface border border-dc-border rounded-md`, no shadow. Keep
the panel treatment for content that is genuinely a unit — flat sections
divided by hairlines are the default, not card soup.

**Tables.** Header cells are `dc-label` with a `border-b border-dc-border-strong`
rule and no fill. No zebra stripes unless the table is very dense, and then
`bg-dc-sunken` rather than lines. Numeric columns right-aligned, `dc-tabular`.

**Status tags.** Not pills. `rounded-xs px-2 py-0.5`, a 2px left border in the
semantic colour, the `-bg` tint behind, text in the full semantic colour.

**Inputs.** `bg-dc-sunken border border-dc-border rounded-xs`; focus moves the
border to `dc-accent-hover` with a 1px outline — no glow ring.

**Flash messages.** `<x-dashcore::flash />` — the status-tag treatment at page
width.

**Empty states.** No illustrations. An eyebrow label, one muted sentence, one
primary action.

**Icons.** Single-weight line icons at 1.5px stroke, in `dc-ink-muted` or
`dc-metal`. Never filled, never multi-colour, never emoji.

**Motion.** Only where it explains a change of state, 150ms, no bounce.

## Don't / do

| Don't | Do |
|---|---|
| `rounded-xl`, `rounded-2xl`, pill badges | 4px maximum — the package enforces it |
| `shadow-md`, `shadow-lg` for elevation | a hairline border |
| Gradients, glassmorphism, `backdrop-blur` panels | flat, opaque surfaces |
| `gray-*`, `zinc-*`, `slate-*`, `blue-500` | the `dc-*` tokens |
| The accent on a button, a link, a badge and a chart line at once | the accent on one thing |
| Bright red/green/yellow status | the muted semantic set on its `-bg` tint |
| Sans for everything | Fraunces for titles, Inter for operation |
| Left-aligned, proportional numbers | right-aligned, `dc-tabular` |
| 32px table rows | 44px and up |
| "Manage your settings here", "Submit" | copy that names the thing: "Save changes", "Send invite" |

## Before calling a screen done

1. Could it be mistaken for a generic admin template? Find the tell above and
   fix it.
2. Count the colours other than ink, border and background. More than two
   (the accent plus one semantic) means colour is decorating, not meaning.
3. Check every radius, shadow and badge against the table.
4. Look at it in dark mode.
