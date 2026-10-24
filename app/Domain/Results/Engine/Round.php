<?php

namespace App\Domain\Results\Engine;

final class Round
{
    /**
     * Two decimals, half up: 12.345 → 12.35. The nudge absorbs float noise (12.345 may be stored as
     * 12.3449999…), which is far smaller than any real difference between two averages.
     */
    public static function halfUp(float $value): float
    {
        return floor($value * 100 + 0.5 + 1e-7) / 100;
    }
}
