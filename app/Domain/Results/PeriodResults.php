<?php

namespace App\Domain\Results;

use App\Domain\Academic\Enums\GradeStage;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradingScale;
use App\Domain\Academic\Models\Term;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\ExamType;
use App\Domain\Exams\Models\Exam;
use App\Domain\People\Models\Enrollment;
use App\Domain\Results\Engine\MentionResolver;
use App\Domain\Results\Engine\Ranker;
use App\Domain\Results\Engine\Round;
use App\Domain\Results\Models\ExamResult;
use App\Domain\School\Models\School;
use App\Domain\School\Settings\SchoolSettings;
use Illuminate\Support\Collection;

/**
 * Semester and year results for one class, worked out from its locked exams' current results each time
 * they are asked for, so they can never lag behind a correction.
 *
 * - Semester: mean of the term's monthly exam averages, then
 *   semesterMonthlyWeight × that + semesterExamWeight × the semester exam's average.
 * - Year: annualS1Weight × semester 1 + annualS2Weight × semester 2.
 * When one part is missing, the other carries the whole weight instead of counting as 0.
 */
final class PeriodResults
{
    /** @return list<array<string, mixed>> */
    public function term(Classroom $classroom, Term $term): array
    {
        $settings = $this->settings($classroom);
        $results = $this->results($classroom, fn ($q) => $q->where('term_id', $term->id));

        $rows = $this->students($classroom)->map(function (Enrollment $e) use ($results, $settings) {
            $mine = $results->get($e->id, collect());
            $monthly = $mine->filter(fn (array $r) => $r['type'] === ExamType::Monthly)->pluck('average');
            $exam = $mine->filter(fn (array $r) => $r['type'] === ExamType::Semester)->sortByDesc('end_date')->first();

            $monthlyAverage = $monthly->isNotEmpty() ? Round::halfUp($monthly->avg()) : null;
            $average = self::weighted([
                [$monthlyAverage, $settings->semesterMonthlyWeight],
                [$exam['average'] ?? null, $settings->semesterExamWeight],
            ]);

            return [
                ...$this->student($e),
                'monthly_average' => $monthlyAverage,
                'monthly_count' => $monthly->count(),
                'exam_average' => $exam['average'] ?? null,
                'average' => $average,
            ];
        });

        return $this->rankAndMention($classroom, $rows);
    }

    /** @return list<array<string, mixed>> */
    public function year(Classroom $classroom): array
    {
        $settings = $this->settings($classroom);
        $terms = Term::where('academic_year_id', $classroom->academic_year_id)->orderBy('sort')->get();
        $byTerm = $terms->take(2)->map(fn (Term $t) => collect($this->term($classroom, $t))->keyBy('enrollment_id'))->values();
        $weights = [$settings->annualS1Weight, $settings->annualS2Weight];

        $rows = $this->students($classroom)->map(function (Enrollment $e) use ($byTerm, $weights) {
            $semesters = $byTerm->map(fn (Collection $term) => $term->get($e->id)['average'] ?? null)->all();

            return [
                ...$this->student($e),
                'semesters' => $semesters,
                'average' => self::weighted(array_map(null, $semesters, array_slice($weights, 0, count($semesters)))),
            ];
        });

        return $this->rankAndMention($classroom, $rows);
    }

    /**
     * Σ(value × weight) over the parts that exist, divided by their weights; null when none exists.
     *
     * @param  list<array{0: ?float, 1: float}>  $parts
     */
    public static function weighted(array $parts): ?float
    {
        $present = array_filter($parts, fn (array $p) => $p[0] !== null && $p[1] > 0);
        $weights = array_sum(array_column($present, 1));

        return $present === [] || $weights <= 0 ? null
            : Round::halfUp(array_sum(array_map(fn (array $p) => $p[0] * $p[1], $present)) / $weights);
    }

    /**
     * Current-version results of the class's locked exams, by enrollment.
     *
     * @return Collection<int, Collection<int, array{type: ExamType, average: float, end_date: ?string}>>
     */
    private function results(Classroom $classroom, callable $scope): Collection
    {
        $exams = Exam::whereIn('status', [ExamStatus::Locked, ExamStatus::Published])->where('results_version', '>', 0)
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroom->id))
            ->tap($scope)->get()->keyBy('id');

        return ExamResult::where('classroom_id', $classroom->id)->whereIn('exam_id', $exams->keys())->get()
            ->filter(fn (ExamResult $r) => $r->version === $exams[$r->exam_id]->results_version)
            ->groupBy('enrollment_id')
            ->map(fn (Collection $rs) => $rs->map(fn (ExamResult $r) => [
                'type' => $exams[$r->exam_id]->type,
                'average' => (float) $r->average,
                'end_date' => $exams[$r->exam_id]->end_date?->toDateString(),
            ]));
    }

    /**
     * @param  Collection<int, covariant array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function rankAndMention(Classroom $classroom, Collection $rows): array
    {
        $ranks = (new Ranker)->rank($rows->mapWithKeys(fn (array $r) => [$r['enrollment_id'] => $r['average']])->all());
        $classroom->loadMissing('gradeLevel');
        $scale = GradingScale::where('stage', GradeStage::forGrade($classroom->gradeLevel->number))->first();
        $mentions = $scale ? new MentionResolver($scale->bands) : null;

        return $rows->map(fn (array $r) => [
            ...$r,
            'rank' => $ranks[$r['enrollment_id']],
            'mention' => $r['average'] !== null ? ($mentions?->resolve($r['average'])['mention'] ?? null) : null,
        ])->values()->all();
    }

    /** @return Collection<int, Enrollment> */
    private function students(Classroom $classroom): Collection
    {
        return Enrollment::where('classroom_id', $classroom->id)->with('student')->orderBy('roll_no')->get();
    }

    /** @return array<string, mixed> */
    private function student(Enrollment $e): array
    {
        return ['enrollment_id' => $e->id, 'roll_no' => $e->roll_no, 'name_km' => $e->student->nameKm(), 'gender' => $e->student->gender->value];
    }

    private function settings(Classroom $classroom): SchoolSettings
    {
        return School::findOrFail($classroom->school_id)->settings;
    }
}
