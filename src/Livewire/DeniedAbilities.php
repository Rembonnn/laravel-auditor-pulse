<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Rembon\LaravelAuditorPulse\Recorders\AuditorRecorder;
use Rembon\LaravelAuditorPulse\Support\Row;

#[Lazy]
class DeniedAbilities extends Card
{
    public function render(): Renderable
    {
        [$abilities, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(AuditorRecorder::DENIED_ABILITY, 'sum', limit: 10)
                ->map(fn (mixed $row): object => (object) [
                    'ability' => Row::string($row, 'key'),
                    'count' => Row::int($row, 'sum'),
                ]),
        );

        return View::make('auditor-pulse::livewire.denied-abilities', [
            'abilities' => $abilities,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
