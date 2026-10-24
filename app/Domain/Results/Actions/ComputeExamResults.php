<?php

namespace App\Domain\Results\Actions;

use App\Domain\Exams\Models\Exam;
use App\Domain\Results\ClassResults;
use App\Domain\Results\Engine\Ranker;
use App\Domain\Results\Models\ExamResult;
use App\Domain\School\Models\School;
use Illuminate\Support\Facades\DB;

/**
 * Writes an exam's results as a new version: every class, then the rank across each grade, in one
 * transaction. The exam points at the new version only once all its rows are in, so readers never see
 * a half-written sheet; running it again simply writes the next version.
 */
class ComputeExamResults
{
    public function __construct(private ClassResults $classResults) {}

    public function handle(Exam $exam): int
    {
        $version = $exam->results_version + 1;
        $rankAcrossGrade = School::findOrFail($exam->school_id)->settings->rankAcrossGrade;
        $now = now();

        $rows = [];
        foreach ($exam->classrooms()->get() as $classroom) {
            foreach ($this->classResults->compute($exam, $classroom)['rows'] as $row) {
                $rows[] = [
                    'school_id' => $exam->school_id,
                    'exam_id' => $exam->id,
                    'enrollment_id' => $row['enrollment_id'],
                    'classroom_id' => $classroom->id,
                    'grade_level_id' => $classroom->grade_level_id, // for grade ranking only, not stored
                    'version' => $version,
                    'total' => $row['total'],
                    'average' => $row['average'],
                    'rankable' => $row['rankable'],
                    'rank_class' => $row['rank_class'],
                    'rank_grade' => null,
                    'mention' => $row['mention'],
                    'passed' => $row['passed'],
                    'absent_count' => $row['absent'],
                    'exempt_count' => $row['exempt'],
                    'computed_at' => $now,
                ];
            }
        }

        if ($rankAcrossGrade) {
            // Keys kept: each rank goes back to the row it was computed for.
            foreach (collect($rows)->groupBy('grade_level_id', preserveKeys: true) as $grade) {
                $ranks = (new Ranker)->rank($grade->map(fn (array $r) => $r['rankable'] ? $r['average'] : null)->all());
                foreach ($ranks as $i => $rank) {
                    $rows[$i]['rank_grade'] = $rank;
                }
            }
        }

        DB::transaction(function () use ($exam, $version, $rows, $now) {
            ExamResult::where('exam_id', $exam->id)->where('version', $version)->delete();
            foreach (array_chunk($rows, 500) as $chunk) {
                ExamResult::insert(array_map(fn (array $r) => array_diff_key($r, ['grade_level_id' => 0, 'rankable' => 0]), $chunk));
            }
            $exam->update(['results_version' => $version, 'results_computed_at' => $now]);

            activity()->performedOn($exam)->withProperties(['version' => $version, 'rows' => count($rows)])->log('exam.results_computed');
        });

        return $version;
    }
}
