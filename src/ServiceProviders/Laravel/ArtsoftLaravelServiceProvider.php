<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector\ServiceProviders\Laravel;

use FranciscoCardoso\ArtsoftConnector\Console\Commands\PublishConfigCommand;
use Illuminate\Support\ServiceProvider;

class ArtsoftLaravelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../../config/artsoft.php' => config_path('artsoft.php'),
        ], 'artsoft-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishConfigCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/artsoft.php',
            'artsoft'
        );
    }
}
