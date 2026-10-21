<?php

namespace App\Domain\Academic\Actions;

use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use Illuminate\Support\Facades\DB;

/**
 * Replaces one grade's (and track's) curriculum for one year in a single transaction:
 * the rows sent are the whole curriculum, so a subject left out is removed.
 */
class SaveCurriculum
{
    /**
     * @param  list<array{subject_id: int, max_score: float, coefficient: float, pass_score: float, sort: int}>  $rows
     */
    public function handle(int $yearId, int $gradeId, ?int $trackId, array $rows, ?float $divisor): void
    {
        DB::transaction(function () use ($yearId, $gradeId, $trackId, $rows, $divisor) {
            $scope = ['academic_year_id' => $yearId, 'grade_level_id' => $gradeId, 'track_id' => $trackId];

            CurriculumSubject::where($scope)->delete();
            foreach ($rows as $row) {
                CurriculumSubject::create([...$scope, ...$row]);
            }

            GradeDivisor::where($scope)->delete();
            if ($divisor !== null) {
                GradeDivisor::create([...$scope, 'divisor' => $divisor]);
            }

            activity()->causedBy(auth()->user())
                ->withProperties([...$scope, 'rows' => $rows, 'divisor' => $divisor])
                ->log('academic.curriculum_saved');
        });
    }
}
