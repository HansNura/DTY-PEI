<?php

namespace App\Support;

class BobbinWeightStandard
{
    public const TARGET_GRAM = 5500;

    public const AX_MIN_GRAM = 5350;

    public const AX_MAX_GRAM = 5600;

    public const AE_MIN_GRAM = 5100;

    public const AE_MAX_GRAM = 5349;

    public const A_MIN_GRAM = 1000;

    public const B_MIN_GRAM = 500;

    public const B_MAX_GRAM = 999;

    public static function gradeFor(float $grams): ?string
    {
        // AX/AE diprioritaskan; A menampung 1.000–5.099 g dan B 500–999 g.
        return match (true) {
            $grams >= self::AX_MIN_GRAM && $grams <= self::AX_MAX_GRAM => 'AX',
            $grams >= self::AE_MIN_GRAM && $grams <= self::AE_MAX_GRAM => 'AE',
            $grams >= self::A_MIN_GRAM && $grams < self::AE_MIN_GRAM => 'A',
            $grams >= self::B_MIN_GRAM && $grams <= self::B_MAX_GRAM => 'B',
            default => null,
        };
    }

    /**
     * @return array{min: float, max: float, center: float}|null
     */
    public static function grossRangeFor(string $grade, int $unitBobbins): ?array
    {
        if (strtoupper($grade) !== 'AX' || $unitBobbins !== 6) {
            return null;
        }

        return [
            'min' => 34.74,
            'max' => 36.25,
            'center' => 35.64,
        ];
    }
}
