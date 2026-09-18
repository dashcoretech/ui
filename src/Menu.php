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
     * @param  array<int, array<string, mixed>>  $menu
     * @return list<array{label: string, items: list<array<string, mixed>>}|array<string, mixed>>
     */
    public static function resolve(array $menu): array
    {
        $resolved = [];

        foreach ($menu as $entry) {
            if (isset($entry['items'])) {
                $items = [];

                foreach ($entry['items'] as $key => $item) {
                    $item = is_string($item) ? ['label' => $item, 'route' => $key] : $item;

                    if (($link = self::link($item)) !== null) {
                        $items[] = $link;
                    }
                }

                if ($items !== []) {
                    $resolved[] = ['label' => (string) $entry['label'], 'items' => $items];
                }

                continue;
            }

            if (($link = self::link($entry)) !== null) {
                $resolved[] = $link;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, href: string, active: bool, icon: ?string}|null
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

        return [
            'label' => (string) $item['label'],
            'href' => $href,
            'active' => (bool) ($item['active'] ?? self::isActive($item, $href)),
            'icon' => $item['icon'] ?? null,
        ];
    }

    /**
     * Whether the current request falls under this entry.
     *
     * By route-name prefix when the entry names a route, so a show page lights
     * its own index; by path prefix otherwise. `match` overrides both with a
     * route pattern (or list of them) for the entry that fronts a section
     * whose routes are not named after it.
     *
     * @param  array<string, mixed>  $item
     */
    private static function isActive(array $item, string $href): bool
    {
        $request = request();

        if (isset($item['match'])) {
            return $request->routeIs(...(array) $item['match']);
        }

        if (isset($item['route'])) {
            return $request->routeIs(Str::before($item['route'], '.index').'*');
        }

        $path = trim((string) parse_url($href, PHP_URL_PATH), '/');

        // The root would prefix-match every page in the app, so it only
        // lights for itself.
        return $path === ''
            ? $request->path() === '/'
            : $request->is($path, $path.'/*');
    }
}
