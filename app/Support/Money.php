<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function toMinorUnits(int|float|string $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException('Nominal harus berupa angka non-negatif dengan maksimal dua angka desimal.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function fromMinorUnits(int $minorUnits): string
    {
        if ($minorUnits < 0) {
            throw new InvalidArgumentException('Minor unit tidak boleh negatif.');
        }

        return intdiv($minorUnits, 100).'.'.str_pad((string) ($minorUnits % 100), 2, '0', STR_PAD_LEFT);
    }
}
