<?php

declare(strict_types=1);

namespace Dashcore\Ui;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class UiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Anonymous components under one prefix: <x-dashcore::shell>. No
        // classes to publish and nothing to register per app — requiring the
        // package is the whole installation on the PHP side.
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'dashcore');

        // The components' own partials, which are not components.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dashcore');
    }
}
