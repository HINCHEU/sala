<?php

namespace App\Domain\Results\Jobs;

use App\Domain\Exams\Models\Exam;
use App\Domain\Results\Actions\ComputeExamResults;
use App\Domain\School\Models\School;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Calculates a locked exam's results. Carries school_id and restores the current school first.
 * Safe to retry: a failed run rolls back without moving the exam's version, so the retry rewrites it.
 */
class CalculateExamResults implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $schoolId, public int $examId) {}

    public function handle(CurrentSchool $current, ComputeExamResults $compute): void
    {
        $current->runAs(School::findOrFail($this->schoolId), function () use ($compute) {
            $compute->handle(Exam::findOrFail($this->examId));
        });
    }
}
