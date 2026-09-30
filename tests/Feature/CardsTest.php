<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use Rembon\LaravelAuditorPulse\Livewire\ActiveUsers;
use Rembon\LaravelAuditorPulse\Livewire\DeniedAbilities;
use Rembon\LaravelAuditorPulse\Livewire\ModelChanges;
use Rembon\LaravelAuditorPulse\Recorders\AuditorRecorder;
use Rembon\LaravelAuditorPulse\Tests\Fixtures\User;

it('shows denied abilities', function (): void {
    Pulse::record(AuditorRecorder::DENIED_ABILITY, 'delete', 3)->sum();
    Pulse::record(AuditorRecorder::DENIED_ABILITY, 'export', 1)->sum();
    Pulse::ingest();

    Livewire::test(DeniedAbilities::class, ['lazy' => false])
        ->assertSeeInOrder(['Denied Abilities', 'delete', '3', 'export', '1']);
});

it('shows the most changed models with readable names', function (): void {
    Pulse::record(AuditorRecorder::MODEL_CHANGE, '["App\\\\Models\\\\Invoice","updated"]')->count();
    Pulse::ingest();

    Livewire::test(ModelChanges::class, ['lazy' => false])
        ->assertSee('Top Model Changes')
        ->assertSee('Invoice')
        ->assertSee('updated');
});

it('resolves users of the auth model through Pulse and labels the others', function (): void {
    $user = User::query()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    Pulse::record(AuditorRecorder::USER_ACTIVITY, $user->getMorphClass().'|'.$user->id)->count();
    Pulse::record(AuditorRecorder::USER_ACTIVITY, 'App\\Models\\Admin|7')->count();
    Pulse::ingest();

    Livewire::test(ActiveUsers::class, ['lazy' => false])
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com')
        ->assertSee('Admin #7');
});

it('shows an empty state', function (string $card): void {
    Livewire::test($card, ['lazy' => false])->assertSee('No results');
})->with([DeniedAbilities::class, ModelChanges::class, ActiveUsers::class]);

it('registers the cards with Livewire', function (string $name): void {
    expect(Blade::render("<livewire:{$name} />"))->toContain('wire:name="'.$name.'"');
})->with(['auditor.denied-abilities', 'auditor.model-changes', 'auditor.active-users']);
