<?php

declare(strict_types=1);

namespace Dashcore\Ui;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Turns an app's menu array into the one shape the sidebar renders.
 *
 * An app owns its menu — what is in it, what it is called, where it goes.
 * The package owns how it looks. This is the seam between the two, so it
 * accepts the forms apps already write rather than making each one convert:
 *
 *     ['label' => 'Overview', 'route' => 'dashboard']
 *     ['label' => 'Docs', 'href' => '/help']
 *     ['label' => 'Inbox', 'route' => 'inbox', 'badge' => 3]                 // a count beside it
 *     ['label' => 'Team', 'route' => 'team', 'icon' => ['M15 19…', 'M12 6…']] // one path's d, or several
 *     ['label' => 'ops@', 'href' => '/m/1', 'hint' => 'Purpose not set', 'tone' => 'warning']
 *     ['label' => 'More', 'collapsed' => true, 'items' => [...]]            // folds; opens on its own pages
 *     ['label' => 'Mailboxes', 'link' => ['label' => 'Manage', 'route' => 'mailboxes'], 'items' => [...]]
 *     ['label' => 'Fleet', 'items' => ['admin.plan' => 'Plan', ...]]        // route => label
 *     ['label' => 'Fleet', 'items' => [['label' => 'Plan', 'route' => ...]]]
 *
 * An entry whose named route does not exist is dropped rather than thrown on:
 * a menu shared across environments should not 500 because one feature is
 * switched off in this one.
 */
class Menu
{
    /**
     * The menu as sections, in order. A heading with its entries is one
     * section; consecutive top-level entries are gathered into one unlabelled
     * section, so three links in a row read as a list rather than as three
     * sections each with a gap above it.
     *
     * @param  array<int, array<string, mixed>>  $menu
     * @return list<array{label: ?string, items: list<array<string, mixed>>, collapsible: bool, open: bool, link: ?array{label: string, href: string}}>
     */
    public static function resolve(array $menu): array
    {
        $sections = [];
        $loose = [];

        $flush = function () use (&$sections, &$loose) {
            if ($loose !== []) {
                $sections[] = ['label' => null, 'items' => $loose, 'collapsible' => false, 'open' => true, 'link' => null];
                $loose = [];
            }
        };

        foreach ($menu as $entry) {
            if (! isset($entry['items'])) {
                if (($link = self::link($entry)) !== null) {
                    $loose[] = $link;
                }

                continue;
            }

            $flush();

            $items = [];

            foreach ($entry['items'] as $key => $item) {
                $item = is_string($item) ? ['label' => $item, 'route' => $key] : $item;

                if (($link = self::link($item)) !== null) {
                    $items[] = $link;
                }
            }

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'label' => (string) $entry['label'],
                'items' => $items,
                // A long menu can fold its less-used sections away. A folded
                // section opens by itself when the page is inside it — a menu
                // that hides where you are is worse than a long one.
                'collapsible' => (bool) ($entry['collapsed'] ?? false),
                'open' => true,
                // One quiet link beside the heading — "Manage" beside a list of
                // mailboxes — for the action about the section as a whole.
                'link' => isset($entry['link']) ? self::headingLink($entry['link']) : null,
            ];
        }

        $flush();

        return self::settle($sections);
    }

    /**
     * Leaves only the most specific of the entries the default rule lit.
     *
     * The prefix rule lights `leads` on the Pipeline board as well as
     * `leads.board`, and `pto.index` on `pto.my` as well as `pto.my`. Of the
     * entries that matched only by that rule, the one with the most route-name
     * or path segments is where you are; the rest were matching as its
     * parents. An entry lit by `active` or `match` is the app speaking, and is
     * left as it said. Then each folded section opens if it holds the page.
     *
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private static function settle(array $sections): array
    {
        $best = max([0, ...array_filter(array_column(array_merge(...array_column($sections, 'items')), 'depth'))]);

        foreach ($sections as &$section) {
            foreach ($section['items'] as &$item) {
                if ($item['depth'] !== null && $item['depth'] < $best) {
                    $item['active'] = false;
                }

                unset($item['depth']);
            }

            unset($item);

            $section['open'] = ! $section['collapsible'] || in_array(true, array_column($section['items'], 'active'), true);
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $link
     * @return array{label: string, href: string}|null
     */
    private static function headingLink(array $link): ?array
    {
        if (isset($link['route']) && ! Route::has($link['route'])) {
            return null;
        }

        return [
            'label' => (string) $link['label'],
            'href' => isset($link['route']) ? route($link['route'], $link['params'] ?? []) : (string) ($link['href'] ?? '#'),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, href: string, active: bool, depth: ?int, icon: string|list<string>|null, badge: ?string, hint: ?string, tone: ?string}|null
     */
    private static function link(array $item): ?array
    {
        if (isset($item['route'])) {
            if (! Route::has($item['route'])) {
                return null;
            }

            $href = route($item['route'], $item['params'] ?? []);
        } else {
            $href = (string) ($item['href'] ?? '#');
        }

        $explicit = isset($item['active']) || isset($item['match']);
        $depth = $explicit ? null : self::depth($item, $href);

        return [
            'label' => (string) $item['label'],
            'href' => $href,
            'active' => (bool) ($item['active'] ?? ($explicit ? self::matches($item) : $depth !== null)),
            // How specific a default-rule match was, for settle(); null when
            // the entry is not lit, or was lit by the app's own say-so.
            'depth' => $depth,
            // One path's `d`, or a list of them: many heroicons are several
            // paths, and joining them into one string is easy to get wrong.
            'icon' => $item['icon'] ?? null,
            // A count beside the label. Zero and null render nothing: an entry
            // showing "0" is a badge asking for attention it does not need.
            'badge' => filled($item['badge'] ?? null) && ($item['badge'] ?? null) !== 0 ? (string) $item['badge'] : null,
            // A second, muted line under the label — a mailbox's purpose — and
            // an optional tone for it when it is a problem to fix rather than
            // a description. Only the hint takes the tone; the label stays ink.
            'hint' => filled($item['hint'] ?? null) ? (string) $item['hint'] : null,
            'tone' => in_array($item['tone'] ?? null, ['success', 'warning', 'danger'], true) ? $item['tone'] : null,
        ];
    }

    /**
     * Whether the current request falls under this entry's `match`: a route
     * pattern (or list of them) for the entry that fronts a section whose
     * routes are not named after it.
     *
     * @param  array<string, mixed>  $item
     */
    private static function matches(array $item): bool
    {
        return isset($item['match']) && request()->routeIs(...(array) $item['match']);
    }

    /**
     * Whether the current request falls under this entry by the default rule,
     * and if so how specifically: the number of segments that matched.
     *
     * By route-name prefix when the entry names a route, so a show page lights
     * its own index (`services.index` covers `services` and `services.*`); by
     * path prefix otherwise. Both stop at a segment boundary, so `leads` does
     * not light on `leadsources`.
     *
     * @param  array<string, mixed>  $item
     */
    private static function depth(array $item, string $href): ?int
    {
        $request = request();

        if (isset($item['route'])) {
            $stem = Str::replaceEnd('.index', '', $item['route']);

            return $request->routeIs($stem, $stem.'.*') ? substr_count($stem, '.') + 1 : null;
        }

        $path = trim((string) parse_url($href, PHP_URL_PATH), '/');

        // The root would prefix-match every page in the app, so it only
        // lights for itself.
        if ($path === '') {
            return $request->path() === '/' ? 1 : null;
        }

        return $request->is($path, $path.'/*') ? substr_count($path, '/') + 1 : null;
    }
}
