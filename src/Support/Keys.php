<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Support;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Encodes and decodes the Pulse keys written by AuditorRecorder.
 *
 * @internal
 */
final class Keys
{
    public static function user(string $type, string $id): string
    {
        return $type.'|'.$id;
    }

    /**
     * @return array{type: string, id: string}
     */
    public static function parseUser(string $key): array
    {
        [$type, $id] = array_pad(explode('|', $key, 2), 2, '');

        return ['type' => $type, 'id' => $id];
    }

    public static function change(string $type, string $event): string
    {
        return (string) json_encode([$type, $event], JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array{type: string, event: string}
     */
    public static function parseChange(string $key): array
    {
        $decoded = json_decode($key, true);

        return [
            'type' => is_array($decoded) && is_string($decoded[0] ?? null) ? $decoded[0] : $key,
            'event' => is_array($decoded) && is_string($decoded[1] ?? null) ? $decoded[1] : '',
        ];
    }

    /**
     * Short, readable label for a morph type or class name.
     */
    public static function label(string $type): string
    {
        return class_basename(Relation::getMorphedModel($type) ?? $type);
    }
}
