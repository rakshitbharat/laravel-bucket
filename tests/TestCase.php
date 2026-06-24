<?php

namespace LaraBucket\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Illuminate\Support\Facades\Storage;
use LaraBucket\LaraBucketServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure storage directory is empty
        Storage::disk('testing_disk')->deleteDirectory('');
    }

    protected function getPackageProviders($app)
    {
        return [
            LaraBucketServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Setup SQLite memory database connection
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Enable LaraBucket Server for testing API endpoints
        $app['config']->set('larabucket.server.enabled', true);
        $app['config']->set('larabucket.server.disk', 'testing_disk');
        $app['config']->set('larabucket.server.admin_email', 'admin@test.com');
        $app['config']->set('larabucket.server.admin_password', 'password123');

        // Setup storage testing disk
        $app['config']->set('filesystems.disks.testing_disk', [
            'driver' => 'local',
            'root'   => storage_path('framework/testing'),
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
