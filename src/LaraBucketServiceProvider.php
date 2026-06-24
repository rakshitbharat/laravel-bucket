<?php

namespace LaraBucket;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use LaraBucket\Storage\LaraBucketAdapter;

class LaraBucketServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/larabucket.php', 'larabucket'
        );
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/larabucket.php' => config_path('larabucket.php'),
            ], 'larabucket-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'larabucket-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/larabucket'),
            ], 'larabucket-views');
        }

        // Register server routes & migrations if enabled
        if (config('larabucket.server.enabled', false)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larabucket');
        }

        // Extend Storage with the larabucket driver
        Storage::extend('larabucket', function ($app, $config) {
            $adapter = new LaraBucketAdapter(
                $config['api_url'],
                $config['bucket'],
                $config['secret']
            );

            return new \Illuminate\Filesystem\FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
