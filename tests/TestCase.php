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
     * A small app: an index with a show page under it, a second section, and
     * a page that renders the shell with whatever menu the test hands it.
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
