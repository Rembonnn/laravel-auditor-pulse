<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pulse\PulseServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Rembon\LaravelAuditor\AuditorServiceProvider;
use Rembon\LaravelAuditorPulse\AuditorPulseServiceProvider;
use Rembon\LaravelAuditorPulse\Recorders\AuditorRecorder;
use Rembon\LaravelAuditorPulse\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            PulseServiceProvider::class,
            AuditorServiceProvider::class,
            AuditorPulseServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config): void {
            $config->set('database.default', 'testing');
            $config->set('cache.default', 'array');
            $config->set('queue.default', 'sync');
            $config->set('auth.providers.users.model', User::class);
            $config->set('auditor.throw_exceptions', true);
            $config->set('auditor.console.enabled', false);
            $config->set('pulse.recorders', [
                AuditorRecorder::class => ['enabled' => true, 'sample_rate' => 1, 'ignore' => []],
            ]);
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/pulse/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/rembon/laravel-auditor/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }
}
