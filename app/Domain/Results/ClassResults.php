<?php

namespace App\Domain\Results;

use App\Domain\Academic\Enums\GradeStage;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeDivisor;
use App\Domain\Academic\Models\GradingScale;
use App\Domain\Exams\Enums\ScoreMark;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Exams\Models\Score;
use App\Domain\People\Models\Enrollment;
use App\Domain\Results\Engine\Curriculum;
use App\Domain\Results\Engine\MentionResolver;
use App\Domain\Results\Engine\Ranker;
use App\Domain\Results\Engine\ResultCalculator;
use App\Domain\Results\Engine\SubjectRule;
use App\Domain\School\Models\School;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * One class's results in one exam, computed from its scores with the engine. Used live while the exam
 * is open (nothing is written) and by the lock job (which writes what this returns), so the preview
 * and the locked sheet always agree.
 */
final class ClassResults
{
    /**
     * @return array{subjects: list<array<string, mixed>>, rows: list<array<string, mixed>>, divisor: ?float}
     */
    public function compute(Exam $exam, Classroom $classroom): array
    {
        $school = School::findOrFail($exam->school_id);
        $sheets = ExamSubjectStatus::where('exam_id', $exam->id)->where('classroom_id', $classroom->id)->pluck('subject_id')->flip();
        $curriculum = CurriculumSubject::forClassroom($classroom)->filter(fn (CurriculumSubject $c) => $sheets->has($c->subject_id))->load('subject');
        $divisor = GradeDivisor::where('academic_year_id', $classroom->academic_year_id)
            ->where('grade_level_id', $classroom->grade_level_id)->where('track_id', $classroom->track_id)->value('divisor');

        $enrollments = Enrollment::where('classroom_id', $classroom->id)->with('student')->orderBy('roll_no')->get();
        $cells = $this->cells($exam, $enrollments);

        $rules = new Curriculum($curriculum->map(fn (CurriculumSubject $c) => new SubjectRule(
            $c->subject_id, (float) $c->max_score, (float) $c->coefficient, (float) $c->pass_score,
        ))->values()->all(), $divisor !== null ? (float) $divisor : null);

        $results = (new ResultCalculator($school->settings))->calculate(
            $enrollments->mapWithKeys(fn (Enrollment $e) => [$e->id => $cells[$e->id] ?? []])->all(), $rules,
        );
        $ranks = (new Ranker)->rank(array_map(fn (array $r) => $r['rankable'] ? $r['average'] : null, $results));
        $mentions = $this->mentions($classroom);

        return [
            'subjects' => $curriculum->values()->map(fn (CurriculumSubject $c) => [
                ...$c->subject->only('id', 'code', 'name_km', 'name_en'),
                'max' => (float) $c->max_score,
                'pass' => (float) $c->pass_score,
            ])->all(),
            'rows' => $enrollments->map(fn (Enrollment $e) => [
                'enrollment_id' => $e->id,
                'roll_no' => $e->roll_no,
                'student_code' => $e->student->student_code,
                'name_km' => $e->student->nameKm(),
                'gender' => $e->student->gender->value,
                'cells' => array_map(fn ($cell) => $cell instanceof ScoreMark ? $cell->letter() : $cell, $cells[$e->id] ?? []),
                ...$results[$e->id],
                'rank_class' => $ranks[$e->id],
                'mention' => $mentions?->resolve($results[$e->id]['average'])['mention'] ?? null,
            ])->values()->all(),
            'divisor' => $divisor !== null ? (float) $divisor : null,
        ];
    }

    /**
     * @param  EloquentCollection<int, Enrollment>  $enrollments
     * @return array<int, array<int, float|ScoreMark>>
     */
    private function cells(Exam $exam, EloquentCollection $enrollments): array
    {
        $cells = [];
        foreach (Score::where('exam_id', $exam->id)->whereIn('enrollment_id', $enrollments->modelKeys())->get() as $score) {
            $cells[$score->enrollment_id][$score->subject_id] = $score->mark === ScoreMark::Present ? (float) $score->score : $score->mark;
        }

        return $cells;
    }

    private function mentions(Classroom $classroom): ?MentionResolver
    {
        $classroom->loadMissing('gradeLevel');
        $scale = GradingScale::where('stage', GradeStage::forGrade($classroom->gradeLevel->number))->first();

        return $scale ? new MentionResolver($scale->bands) : null;
    }
}
