<?php

namespace App\Domain\Exams\Enums;

/** What a filled score cell holds besides a number: typed as A (absent) or E (exempt), or អ / ល in Khmer. */
enum ScoreMark: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Exempt = 'exempt';

    public static function fromLetter(string $letter): ?self
    {
        return match (mb_strtolower($letter)) {
            'a', 'អ' => self::Absent,
            'e', 'ល' => self::Exempt,
            default => null,
        };
    }

    /** How the cell shows it: the score itself, or A / E. */
    public function letter(): ?string
    {
        return match ($this) {
            self::Absent => 'A',
            self::Exempt => 'E',
            self::Present => null,
        };
    }
}
