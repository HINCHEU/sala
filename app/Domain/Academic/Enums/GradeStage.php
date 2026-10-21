<?php

namespace App\Domain\Academic\Enums;

/** MoEYS 6 + 3 + 3, plus preschool. Grading scales are set per stage. */
enum GradeStage: string
{
    case Preschool = 'preschool';
    case Primary = 'primary';
    case LowerSecondary = 'lower_secondary';
    case UpperSecondary = 'upper_secondary';

    public static function forGrade(int $number): self
    {
        return match (true) {
            $number === 0 => self::Preschool,
            $number <= 6 => self::Primary,
            $number <= 9 => self::LowerSecondary,
            default => self::UpperSecondary,
        };
    }
}
