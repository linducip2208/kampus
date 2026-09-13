<?php

namespace App\Models;

class Setting extends CampusModel
{
    public static function value(string $group, string $key, mixed $default = null, ?string $universityId = null): mixed
    {
        $record = static::query()
            ->where('group', $group)
            ->where('key', $key)
            ->when($universityId, fn ($query) => $query->where('university_id', $universityId))
            ->first();

        if (! $record) {
            return $default;
        }

        $value = $record->value;
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
