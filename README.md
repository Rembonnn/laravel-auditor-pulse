# Laravel Auditor for Pulse

[Laravel Pulse](https://pulse.laravel.com) cards for [Laravel Auditor](https://github.com/Rembonnn/laravel-auditor):

- **Denied Abilities** — which Gate abilities are refused most often.
- **Top Model Changes** — which models change most, per event (created, updated, deleted…).
- **Most Active Users (audited)** — who causes the most audited requests, jobs and commands.

Requires PHP 8.3+, Laravel 12 or 13, Laravel Auditor 3 and Laravel Pulse 1.4+.

## Installation

```bash
composer require rembon/laravel-auditor-pulse
```

Add the recorder to `config/pulse.php`:

```php
'recorders' => [
    // ...
    \Rembon\LaravelAuditorPulse\Recorders\AuditorRecorder::class => [
        'enabled' => env('PULSE_AUDITOR_ENABLED', true),
        'sample_rate' => env('PULSE_AUDITOR_SAMPLE_RATE', 1),
        'ignore' => [
            // Regular expressions matched against the ability name,
            // "{user_type}|{user_id}" and the auditable type.
            // '/^viewNova$/',
        ],
    ],
],
```

Then add the cards to your Pulse dashboard (`resources/views/vendor/pulse/dashboard.blade.php`):

```blade
<livewire:auditor.denied-abilities cols="4" />
<livewire:auditor.model-changes cols="4" />
<livewire:auditor.active-users cols="4" />
```

## How it works

The recorder listens to Laravel Auditor's `EntryRecorded` and `ModelChangeRecorded` events,
which are dispatched after an entry was persisted. Nothing is read back from the audit
tables, so the cards cost nothing extra on the database that holds your audit trail.

| Pulse type | Aggregate | Key |
|---|---|---|
| `auditor_denied_ability` | sum | ability name |
| `auditor_user_activity` | count | `{user_type}\|{user_id}` |
| `auditor_model_change` | count | `["{auditable_type}","{event}"]` |

Users of your default auth model are shown through Pulse's user resolver, so a custom
`Pulse::user()` callback applies. Other user types are shown as `Type #id`.

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE](LICENSE).
