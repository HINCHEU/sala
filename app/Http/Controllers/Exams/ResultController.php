<?php

namespace App\Http\Controllers\Exams;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\Term;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Policies\ExamPolicy;
use App\Domain\Results\ClassResults;
use App\Domain\Results\Enums\ChangeRequestStatus;
use App\Domain\Results\Models\ExamResult;
use App\Domain\Results\Models\ScoreChangeRequest;
use App\Domain\Results\PeriodResults;
use App\Domain\School\Enums\Permission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Result sheets. While an exam is open they are worked out live from the scores (nothing written);
 * once it is locked they show the stored results of the current version, with class and grade ranks.
 */
class ResultController extends Controller
{
    public function __construct(private ExamPolicy $policy) {}

    public function classroom(Request $request, Exam $exam, ClassResults $classResults): Response
    {
        $classroom = $exam->classrooms()->where('classrooms.id', $request->integer('classroom'))->first();
        abort_unless($classroom !== null, 404);
        $user = $request->user();
        abort_unless($this->policy->viewScores($user, $exam, $classroom), 403);

        $sheet = $classResults->compute($exam, $classroom);
        $subjects = Subject::whereKey(array_column($sheet['subjects'], 'id'))->get()->keyBy('id');
        $stored = $exam->isLocked() && $exam->results_version > 0
            ? ExamResult::where('exam_id', $exam->id)->where('version', $exam->results_version)->where('classroom_id', $classroom->id)->get()->keyBy('enrollment_id')
            : null;

        $rows = collect($sheet['rows'])->map(function (array $row) use ($stored) {
            $result = $stored?->get($row['enrollment_id']);

            // Locked: the stored version is the record; the live numbers are only shown while open.
            return $result ? [
                ...$row,
                'total' => (float) $result->total, 'average' => (float) $result->average,
                'rank_class' => $result->rank_class, 'rank_grade' => $result->rank_grade,
                'mention' => $result->mention, 'passed' => $result->passed,
            ] : [...$row, 'rank_grade' => null];
        })->sortBy(fn (array $r) => [$r['rank_class'] ?? PHP_INT_MAX, $r['roll_no']])->values();

        return Inertia::render('Exams/Results', [
            'exam' => [
                ...$exam->only('id', 'name', 'results_version'),
                'status' => $exam->status->value,
                'locked' => $exam->isLocked(),
                'computed_at' => $exam->results_computed_at?->format('Y-m-d H:i'),
                'term_id' => $exam->term_id,
            ],
            'classroom' => [...$classroom->only('id', 'name', 'grade_level_id')],
            'classrooms' => $exam->classrooms()->orderBy('name')->get()
                ->filter(fn (Classroom $c) => $this->policy->viewScores($user, $exam, $c))
                ->map(fn (Classroom $c) => $c->only('id', 'name'))->values(),
            'subjects' => collect($sheet['subjects'])->map(fn (array $s) => [
                ...$s,
                'can_request' => $this->policy->requestScoreChange($user, $exam, $classroom, $subjects[$s['id']]),
            ])->all(),
            'rows' => $rows,
            'divisor' => $sheet['divisor'],
            'statistics' => $this->statistics($sheet['subjects'], collect($sheet['rows'])),
            'changes' => $this->changes($exam, $classroom),
            'canDecide' => $user->can(Permission::ApproveScoreChanges->value),
        ]);
    }

    public function grade(Request $request, Exam $exam): Response
    {
        $user = $request->user();
        $grade = GradeLevel::findOrFail($request->integer('grade'));
        $classrooms = $exam->classrooms()->where('grade_level_id', $grade->id)->orderBy('name')->get();
        abort_unless($exam->isLocked() && $exam->results_version > 0 && $classrooms->isNotEmpty(), 404);
        abort_unless($user->can(Permission::ManageExams->value)
            || $classrooms->contains(fn (Classroom $c) => $this->policy->approveSubjectScores($user, $exam, $c)), 403);

        $results = ExamResult::where('exam_id', $exam->id)->where('version', $exam->results_version)
            ->whereIn('classroom_id', $classrooms->modelKeys())->with('enrollment.student')->get();
        $names = $classrooms->pluck('name', 'id');

        return Inertia::render('Exams/GradeResults', [
            'exam' => [...$exam->only('id', 'name'), 'status' => $exam->status->value],
            'grade' => $grade->only('id', 'name_km', 'name_en'),
            'rows' => $results->sortBy(fn (ExamResult $r) => [$r->rank_grade ?? PHP_INT_MAX, $names[$r->classroom_id]])->values()
                ->map(fn (ExamResult $r) => [
                    'enrollment_id' => $r->enrollment_id,
                    'name_km' => $r->enrollment->student->nameKm(),
                    'gender' => $r->enrollment->student->gender->value,
                    'classroom' => $names[$r->classroom_id],
                    'average' => (float) $r->average,
                    'rank_grade' => $r->rank_grade,
                    'rank_class' => $r->rank_class,
                    'mention' => $r->mention,
                    'passed' => $r->passed,
                ]),
        ]);
    }

    /** Semester (with ?term=) or whole-year results for one class. */
    public function period(Request $request, Classroom $classroom, PeriodResults $periods): Response
    {
        abort_unless($this->policy->viewClassResults($request->user(), $classroom), 403);
        $terms = Term::where('academic_year_id', $classroom->academic_year_id)->orderBy('sort')->get(['id', 'name_km', 'name_en']);
        $term = $request->filled('term') ? $terms->firstWhere('id', $request->integer('term')) : null;
        abort_if($request->filled('term') && ! $term, 404);

        return Inertia::render('Exams/PeriodResults', [
            'classroom' => $classroom->only('id', 'name'),
            'terms' => $terms,
            'term' => $term?->only('id', 'name_km', 'name_en'),
            'rows' => $term ? $periods->term($classroom, $term) : $periods->year($classroom),
        ]);
    }

    /**
     * Per subject: class average, pass rate, and how many sat / passed — total and girls (សរុប / ស្រី).
     * An absent student sat and scored 0; an exempt one is left out.
     *
     * @param  list<array<string, mixed>>  $subjects
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function statistics(array $subjects, $rows): array
    {
        return array_map(function (array $subject) use ($rows) {
            $sat = $rows->filter(fn (array $r) => is_float($r['cells'][$subject['id']] ?? null) || ($r['cells'][$subject['id']] ?? null) === 'A');
            $scores = $sat->map(fn (array $r) => is_float($r['cells'][$subject['id']]) ? $r['cells'][$subject['id']] : 0.0);
            $passed = $sat->filter(fn (array $r) => is_float($r['cells'][$subject['id']]) && $r['cells'][$subject['id']] >= $subject['pass']);

            return [
                'subject_id' => $subject['id'],
                'average' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
                'sat' => $sat->count(),
                'sat_female' => $sat->where('gender', 'female')->count(),
                'passed' => $passed->count(),
                'passed_female' => $passed->where('gender', 'female')->count(),
                'pass_rate' => $sat->isNotEmpty() ? round($passed->count() / $sat->count() * 100, 1) : null,
            ];
        }, $subjects);
    }

    /** @return list<array<string, mixed>> */
    private function changes(Exam $exam, Classroom $classroom): array
    {
        return ScoreChangeRequest::where('exam_id', $exam->id)
            ->whereHas('enrollment', fn ($q) => $q->where('classroom_id', $classroom->id))
            ->with('enrollment.student', 'subject', 'requester:id,name', 'decider:id,name')->latest('id')->get()
            ->map(fn (ScoreChangeRequest $c) => [
                'id' => $c->id,
                'student' => $c->enrollment?->student?->nameKm(),
                'subject' => $c->subject?->code,
                'old_value' => $c->old_value,
                'new_value' => $c->new_value,
                'reason' => $c->reason,
                'status' => $c->status->value,
                'requested_by' => $c->requester?->name,
                'decided_by' => $c->decider?->name,
                'decision_note' => $c->decision_note,
                'pending' => $c->status === ChangeRequestStatus::Pending,
            ])->all();
    }
}
