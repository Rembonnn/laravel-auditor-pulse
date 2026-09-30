<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Recorders;

use Carbon\CarbonImmutable;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Ignores;
use Laravel\Pulse\Recorders\Concerns\Sampling;
use Rembon\LaravelAuditor\Events\EntryRecorded;
use Rembon\LaravelAuditor\Events\ModelChangeRecorded;
use Rembon\LaravelAuditorPulse\Support\Keys;

/**
 * Feeds Laravel Auditor events into Pulse.
 *
 * Recorded types:
 *  - auditor_denied_ability (sum)  key: ability name
 *  - auditor_user_activity  (count) key: "{user_type}|{user_id}"
 *  - auditor_model_change   (count) key: json [auditable_type, event]
 *
 * The "ignore" patterns are matched against the ability name, the
 * "{user_type}|{user_id}" key and the auditable type.
 */
class AuditorRecorder
{
    use Ignores;
    use Sampling;

    public const string DENIED_ABILITY = 'auditor_denied_ability';

    public const string USER_ACTIVITY = 'auditor_user_activity';

    public const string MODEL_CHANGE = 'auditor_model_change';

    /**
     * @var list<class-string>
     */
    public array $listen = [
        EntryRecorded::class,
        ModelChangeRecorded::class,
    ];

    public function __construct(protected Pulse $pulse) {}

    public function record(EntryRecorded|ModelChangeRecorded $event): void
    {
        if (! $this->shouldSample()) {
            return;
        }

        $event instanceof EntryRecorded ? $this->recordEntry($event) : $this->recordChange($event);
    }

    private function recordEntry(EntryRecorded $event): void
    {
        $entry = $event->entry;
        $timestamp = CarbonImmutable::parse($entry->startedAt);

        if ($entry->userType !== null && $entry->userId !== null) {
            $key = Keys::user($entry->userType, $entry->userId);

            if (! $this->shouldIgnore($key)) {
                $this->pulse->record(self::USER_ACTIVITY, $key, timestamp: $timestamp)->count();
            }
        }

        foreach ($entry->abilities as $check) {
            $ability = $check['ability'] ?? null;

            if (! is_string($ability) || ($check['result'] ?? null) === true || $this->shouldIgnore($ability)) {
                continue;
            }

            $count = $check['count'] ?? 1;

            $this->pulse->record(self::DENIED_ABILITY, $ability, is_int($count) ? $count : 1, $timestamp)->sum();
        }
    }

    private function recordChange(ModelChangeRecorded $event): void
    {
        $change = $event->change;

        if ($this->shouldIgnore($change->auditableType)) {
            return;
        }

        $this->pulse->record(
            self::MODEL_CHANGE,
            Keys::change($change->auditableType, $change->event->value),
            timestamp: CarbonImmutable::parse($change->createdAt),
        )->count();
    }
}
