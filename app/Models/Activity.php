<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::saving(function (self $activity): void {
            $properties = self::normalizeProperties($activity->properties ?? []);
            $causer = $activity->causer;

            if ($causer && method_exists($causer, 'getRoleNames')) {
                $properties = $properties->put('actor', [
                    'id' => $causer->getKey(),
                    'name' => $causer->name ?? $causer->email ?? 'Unknown User',
                    'roles' => $causer->getRoleNames()->values()->all(),
                ]);
            }

            if (! $properties->has('context')) {
                $properties = $properties->put('context', []);
            }

            $activity->properties = $properties;
        });
    }

    public function getActorRolesAttribute(): array
    {
        $properties = self::normalizeProperties($this->properties ?? []);

        return Arr::get($properties->toArray(), 'actor.roles', []);
    }

    private static function normalizeProperties(mixed $value): Collection
    {
        return match (true) {
            $value instanceof Collection => $value,
            is_array($value) => collect($value),
            default => collect(json_decode((string) $value, true) ?: []),
        };
    }
}
