<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse;

use Illuminate\Support\ServiceProvider;
use Livewire\LivewireManager;
use Rembon\LaravelAuditorPulse\Livewire\ActiveUsers;
use Rembon\LaravelAuditorPulse\Livewire\DeniedAbilities;
use Rembon\LaravelAuditorPulse\Livewire\ModelChanges;

class AuditorPulseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auditor-pulse');

        $this->callAfterResolving('livewire', function (LivewireManager $livewire): void {
            $livewire->component('auditor.denied-abilities', DeniedAbilities::class);
            $livewire->component('auditor.model-changes', ModelChanges::class);
            $livewire->component('auditor.active-users', ActiveUsers::class);
        });
    }
}
