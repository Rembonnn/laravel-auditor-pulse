<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Livewire;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Facades\Pulse;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Rembon\LaravelAuditorPulse\Recorders\AuditorRecorder;
use Rembon\LaravelAuditorPulse\Support\Keys;
use Rembon\LaravelAuditorPulse\Support\Row;

#[Lazy]
class ActiveUsers extends Card
{
    public function render(): Renderable
    {
        [$users, $time, $runAt] = $this->remember(function (): Collection {
            $counts = $this->aggregate(AuditorRecorder::USER_ACTIVITY, 'count', limit: 10)
                ->map(fn (mixed $row): array => [...Keys::parseUser(Row::string($row, 'key')), 'key' => Row::string($row, 'key'), 'count' => Row::int($row, 'count')]);

            // Users of the default auth model go through Pulse's user resolver,
            // so a customised Pulse::user() applies here too.
            $authTypes = self::authUserTypes();
            $resolved = Pulse::resolveUsers($counts->whereIn('type', $authTypes)->pluck('id')->values());

            return $counts->map(fn (array $row): object => (object) [
                'key' => $row['key'],
                'user' => in_array($row['type'], $authTypes, true) ? $resolved->find($row['id']) : null,
                'name' => Keys::label($row['type']).' #'.$row['id'],
                'extra' => $row['type'],
                'count' => $row['count'],
            ]);
        });

        return View::make('auditor-pulse::livewire.active-users', [
            'users' => $users,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }

    /**
     * @return list<string>
     */
    private static function authUserTypes(): array
    {
        $guard = Config::get('auth.guards.'.Config::string('auth.defaults.guard', 'web'));
        $provider = is_array($guard) && is_string($guard['provider'] ?? null) ? Config::get('auth.providers.'.$guard['provider']) : null;
        $model = is_array($provider) && is_string($provider['model'] ?? null) ? $provider['model'] : null;

        if ($model === null || ! is_a($model, Model::class, true)) {
            return [];
        }

        return array_values(array_unique([$model, (new $model)->getMorphClass()]));
    }
}
