<?php

namespace App\Services;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class NumberSequenceService
{
    public function ensure(string $universityId, string $key, string $format): NumberSequence
    {
        return NumberSequence::query()->firstOrCreate(
            ['university_id' => $universityId, 'key' => $key],
            ['format' => $format, 'next_value' => 1],
        );
    }

    public function next(string $universityId, string $key, array $replacements = []): string
    {
        return DB::transaction(function () use ($universityId, $key, $replacements) {
            $sequence = NumberSequence::query()->where('university_id', $universityId)->where('key', $key)->lockForUpdate()->firstOrFail();
            $number = $sequence->next_value;
            $sequence->increment('next_value');
            $tokens = array_merge($replacements, ['number' => str_pad((string) $number, 5, '0', STR_PAD_LEFT)]);
            return str_replace(array_map(fn (string $token) => '{'.$token.'}', array_keys($tokens)), array_values($tokens), $sequence->format);
        });
    }
}
