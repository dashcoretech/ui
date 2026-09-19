<?php

declare(strict_types=1);

namespace Dashcore\Ui\Tests;

use Dashcore\Ui\UiServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [UiServiceProvider::class];
    }

    /**
     * A small app: an index with a show page under it, a second section, some
     * siblings, and a page that renders the shell with whatever menu the test
     * hands it.
     */
    protected function defineRoutes($router): void
    {
        $page = fn () => Blade::render(
            '<x-dashcore::shell :menu="$menu" product="Test">page body</x-dashcore::shell>',
            ['menu' => app('test.menu')],
        );

        Route::get('/', $page)->name('home');
        Route::get('/services', $page)->name('services.index');
        Route::get('/services/{id}', $page)->name('services.show');
        Route::get('/audit', $page)->name('audit.index');
        Route::get('/help', $page);

        // Siblings, for the most-specific rule: an entry named without
        // .index beside one of its own children (sales), and an .index beside
        // a sibling that is its own entry (hr).
        Route::get('/leads', $page)->name('leads');
        Route::get('/leads/board', $page)->name('leads.board');
        Route::get('/leads/{id}', $page)->name('leads.show');
        Route::get('/lead-sources', $page)->name('leadsources.index');
        Route::get('/pto', $page)->name('pto.index');
        Route::get('/pto/mine', $page)->name('pto.my');
        Route::get('/pto/{id}', $page)->name('pto.show');
    }

    protected function defineEnvironment($app): void
    {
        $app->instance('test.menu', [
            ['label' => 'Overview', 'route' => 'home'],
            ['label' => 'Fleet', 'items' => [
                'services.index' => 'Services',
                'missing.route' => 'Switched off here',
            ]],
            ['label' => 'Activity', 'items' => [
                ['label' => 'Audit', 'route' => 'audit.index'],
            ]],
            ['label' => 'Help', 'href' => '/help'],
        ]);
    }
}
