<?php

namespace App\Domain\Exams;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * One subject's score sheet for a class, to fill in Excel and import back. Rows are matched by
 * student code (column B) on import, so the order and the names may change freely.
 */
class ScoreSheetExport implements FromArray, ShouldAutoSize
{
    public const CODE_COLUMN = 1;

    public const SCORE_COLUMN = 4;

    /** @param list<list<mixed>> $rows */
    public function __construct(private array $rows, private string $scoreHeading) {}

    /** @return list<list<mixed>> */
    public function array(): array
    {
        return [
            [__('people.roll_no'), __('people.student_code'), __('people.name_km'), __('people.name_en'), $this->scoreHeading],
            ...$this->rows,
        ];
    }
}
