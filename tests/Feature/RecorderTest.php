<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Pulse\Facades\Pulse;
use Rembon\LaravelAuditor\Data\EntryData;
use Rembon\LaravelAuditor\Data\ModelChangeData;
use Rembon\LaravelAuditor\Enums\ChangeEvent;
use Rembon\LaravelAuditor\Enums\EntryType;
use Rembon\LaravelAuditor\Events\EntryRecorded;
use Rembon\LaravelAuditor\Events\ModelChangeRecorded;
use Rembon\LaravelAuditor\Recorder;
use Rembon\LaravelAuditorPulse\Recorders\AuditorRecorder;

function entry(array $overrides = []): EntryData
{
    return new EntryData(...[
        'ulid' => '01K6A0000000000000000000AA',
        'correlationId' => 'c-1',
        'type' => EntryType::Http,
        'name' => 'posts.destroy',
        'startedAt' => now()->format('Y-m-d H:i:s.u'),
        'completedAt' => now()->format('Y-m-d H:i:s.u'),
        ...$overrides,
    ]);
}

function change(string $type = 'post', ChangeEvent $event = ChangeEvent::Updated): ModelChangeData
{
    return new ModelChangeData(
        ulid: (string) str()->ulid(), entryUlid: null, correlationId: 'c-1', auditableType: $type, auditableId: '1',
        event: $event, oldValues: null, newValues: null, userType: null, userId: null, createdAt: now()->format('Y-m-d H:i:s.u'),
    );
}

/**
 * @return array<string, int>
 */
function totals(string $type, string $aggregate): array
{
    Pulse::ingest();

    return DB::table('pulse_aggregates')
        ->where('type', $type)->where('aggregate', $aggregate)->where('period', 60)
        ->pluck('value', 'key')->map(fn ($value): int => (int) $value)->all();
}

it('sums denied abilities and ignores granted ones', function (): void {
    event(new EntryRecorded(entry(['abilities' => [
        ['ability' => 'delete', 'result' => false, 'arguments' => [], 'count' => 3],
        ['ability' => 'update', 'result' => true, 'arguments' => [], 'count' => 5],
        ['ability' => 'export', 'result' => null, 'arguments' => []],
    ]])));

    expect(totals(AuditorRecorder::DENIED_ABILITY, 'sum'))->toBe(['delete' => 3, 'export' => 1]);
});

it('counts entries per user', function (): void {
    event(new EntryRecorded(entry(['userType' => 'user', 'userId' => '5'])));
    event(new EntryRecorded(entry(['userType' => 'user', 'userId' => '5'])));
    event(new EntryRecorded(entry(['userType' => 'admin', 'userId' => '5'])));
    event(new EntryRecorded(entry())); // guest

    expect(totals(AuditorRecorder::USER_ACTIVITY, 'count'))->toEqualCanonicalizing(['user|5' => 2, 'admin|5' => 1]);
});

it('counts model changes per type and event', function (): void {
    event(new ModelChangeRecorded(change()));
    event(new ModelChangeRecorded(change()));
    event(new ModelChangeRecorded(change('post', ChangeEvent::Deleted)));
    event(new ModelChangeRecorded(change('App\\Models\\Order', ChangeEvent::Created)));

    expect(totals(AuditorRecorder::MODEL_CHANGE, 'count'))->toEqualCanonicalizing([
        '["post","updated"]' => 2,
        '["post","deleted"]' => 1,
        '["App\\\\Models\\\\Order","created"]' => 1,
    ]);
});

it('honours the ignore patterns', function (): void {
    config(['pulse.recorders.'.AuditorRecorder::class.'.ignore' => ['/^viewNova$/', '/^admin\|/', '/Order$/']]);

    event(new EntryRecorded(entry([
        'userType' => 'admin', 'userId' => '1',
        'abilities' => [['ability' => 'viewNova', 'result' => false, 'arguments' => []], ['ability' => 'delete', 'result' => false, 'arguments' => []]],
    ])));
    event(new ModelChangeRecorded(change('App\\Models\\Order')));

    expect(totals(AuditorRecorder::DENIED_ABILITY, 'sum'))->toBe(['delete' => 1])
        ->and(totals(AuditorRecorder::USER_ACTIVITY, 'count'))->toBe([])
        ->and(totals(AuditorRecorder::MODEL_CHANGE, 'count'))->toBe([]);
});

it('honours the sample rate', function (): void {
    config(['pulse.recorders.'.AuditorRecorder::class.'.sample_rate' => 0]);

    event(new EntryRecorded(entry(['userType' => 'user', 'userId' => '5'])));

    expect(totals(AuditorRecorder::USER_ACTIVITY, 'count'))->toBe([]);
});

it('records what Laravel Auditor records, end to end', function (): void {
    Gate::define('export', fn ($user = null): bool => false);
    $recorder = app(Recorder::class);
    $entry = $recorder->start(EntryType::Command, 'reports:export');

    Gate::forUser(null)->denies('export');
    Gate::forUser(null)->denies('export');

    $recorder->finish($entry);

    expect(totals(AuditorRecorder::DENIED_ABILITY, 'sum'))->toBe(['export' => 2]);
});
