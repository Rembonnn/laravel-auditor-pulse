<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Rembon\LaravelAuditorPulse\Recorders\AuditorRecorder;
use Rembon\LaravelAuditorPulse\Support\Keys;
use Rembon\LaravelAuditorPulse\Support\Row;

#[Lazy]
class ModelChanges extends Card
{
    public function render(): Renderable
    {
        [$changes, $time, $runAt] = $this->remember(
            fn () => $this->aggregate(AuditorRecorder::MODEL_CHANGE, 'count', limit: 10)
                ->map(function (mixed $row): object {
                    $key = Keys::parseChange(Row::string($row, 'key'));

                    return (object) [
                        'key' => Row::string($row, 'key'),
                        'type' => $key['type'],
                        'label' => Keys::label($key['type']),
                        'event' => $key['event'],
                        'count' => Row::int($row, 'count'),
                    ];
                }),
        );

        return View::make('auditor-pulse::livewire.model-changes', [
            'changes' => $changes,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
