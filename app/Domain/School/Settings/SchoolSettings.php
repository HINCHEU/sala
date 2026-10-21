<?php

namespace App\Domain\School\Settings;

use Spatie\LaravelData\Data;

/**
 * Per-school rules, stored as schools.settings JSON. Defaults follow the architecture plan's Decisions table.
 */
final class SchoolSettings extends Data
{
    /**
     * @param  array<int, array<string, mixed>>  $promotionRules
     * @param  array<string, bool>  $modules
     */
    public function __construct(
        public string $locale = 'km',
        public string $averageMethod = 'divisor',      // divisor | weighted | simple
        public float $semesterMonthlyWeight = 0.5,
        public float $semesterExamWeight = 0.5,
        public float $annualS1Weight = 0.5,
        public float $annualS2Weight = 0.5,
        public string $absentSubjectPolicy = 'zero',   // zero | exclude_from_rank
        public bool $rankAcrossGrade = true,
        public string $scoreApproverRole = 'homeroom_teacher',
        public string $attendanceMode = 'daily',       // daily | period | both
        public array $promotionRules = [],
        public array $modules = ['finance' => false],
        public bool $khmerNumerals = false,
    ) {}
}
