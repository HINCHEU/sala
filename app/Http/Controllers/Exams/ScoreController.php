<?php

namespace App\Http\Controllers\Exams;

use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Actions\EnterScores;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Exams\Models\Score;
use App\Domain\Exams\Policies\ExamPolicy;
use App\Domain\Exams\ScoreSheetExport;
use App\Domain\People\Models\Enrollment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The score sheet of one class in one exam: students down, subjects across. Cells save through
 * update() as JSON, so typing never reloads the page.
 */
class ScoreController extends Controller
{
    public function __construct(private ExamPolicy $policy) {}

    public function sheet(Request $request, Exam $exam): Response
    {
        $classroom = $this->classroom($request, $exam);
        $user = $request->user();
        abort_unless($this->policy->viewScores($user, $exam, $classroom), 403);

        $subjects = $this->subjects($request, $exam, $classroom);
        abort_if($subjects->isEmpty(), 403);
        $enrollments = Enrollment::where('classroom_id', $classroom->id)->with('student')->orderBy('roll_no')->get();
        $scores = Score::where('exam_id', $exam->id)->whereIn('subject_id', $subjects->pluck('id'))
            ->whereIn('enrollment_id', $enrollments->modelKeys())->get();

        return Inertia::render('Exams/Scores', [
            'exam' => [...$exam->only('id', 'name'), 'status' => $exam->status->value, 'open' => $exam->isOpen()],
            'classroom' => $classroom->only('id', 'name'),
            // Other classes of this exam the user may open, for the switcher.
            'classrooms' => $exam->classrooms()->orderBy('name')->get()
                ->filter(fn (Classroom $c) => $this->policy->viewScores($user, $exam, $c))
                ->map(fn (Classroom $c) => $c->only('id', 'name'))->values(),
            'subjects' => $subjects->values(),
            'students' => $enrollments->map(fn (Enrollment $e) => [
                'enrollment_id' => $e->id,
                'roll_no' => $e->roll_no,
                'student_code' => $e->student->student_code,
                'name_km' => $e->student->nameKm(),
                'name_en' => $e->student->nameEn(),
                'gender' => $e->student->gender->value,
            ]),
            'scores' => $scores->mapWithKeys(fn (Score $s) => ["{$s->enrollment_id}-{$s->subject_id}" => $s->display()]),
            'focus' => $request->integer('subject') ?: null,
        ]);
    }

    public function update(Request $request, Exam $exam, EnterScores $enter): JsonResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'cells' => ['required', 'array', 'min:1', 'max:2000'],
            'cells.*.enrollment_id' => ['required', 'integer'],
            'cells.*.subject_id' => ['required', 'integer'],
            'cells.*.value' => ['present', 'nullable', 'string', 'max:10'],
        ]);
        $classroom = $this->classroom($request, $exam, (int) $data['classroom_id']);

        abort_if(! $exam->isOpen(), 423, __('exams.errors.not_open_for_scores'));
        $this->authorizeSubjects($request, $exam, $classroom, array_column($data['cells'], 'subject_id'));

        return response()->json(['cells' => $enter->handle($exam, $classroom, array_map(fn (array $c) => [
            'enrollment_id' => (int) $c['enrollment_id'],
            'subject_id' => (int) $c['subject_id'],
            'value' => $c['value'] ?? null,
        ], $data['cells']), $request->user())]);
    }

    public function export(Request $request, Exam $exam): BinaryFileResponse
    {
        $classroom = $this->classroom($request, $exam);
        abort_unless($this->policy->viewScores($request->user(), $exam, $classroom), 403);
        $subject = $this->subjects($request, $exam, $classroom)->firstWhere('id', $request->integer('subject'));
        abort_unless($subject !== null, 404);

        $scores = Score::where('exam_id', $exam->id)->where('subject_id', $subject['id'])->get()->keyBy('enrollment_id');
        $rows = Enrollment::where('classroom_id', $classroom->id)->with('student')->orderBy('roll_no')->get()
            ->map(fn (Enrollment $e) => [$e->roll_no, $e->student->student_code, $e->student->nameKm(), $e->student->nameEn(), $scores->get($e->id)?->display()])
            ->all();

        $heading = "{$subject['code']} (/{$subject['max_score']})";

        return Excel::download(new ScoreSheetExport($rows, $heading), "scores-{$classroom->name}-{$subject['code']}.xlsx");
    }

    /** Fills one subject from a sheet: rows matched by student code; each cell checked like a typed one. */
    public function import(Request $request, Exam $exam, EnterScores $enter): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
            'subject_id' => ['required', 'integer'],
        ]);
        $classroom = $this->classroom($request, $exam, $request->integer('classroom_id'));
        abort_if(! $exam->isOpen(), 423, __('exams.errors.not_open_for_scores'));
        $subjectId = $request->integer('subject_id');
        $this->authorizeSubjects($request, $exam, $classroom, [$subjectId]);

        $byCode = Enrollment::where('classroom_id', $classroom->id)->with('student:id,student_code')->get()
            ->mapWithKeys(fn (Enrollment $e) => [mb_strtolower($e->student->student_code) => $e->id]);

        $cells = [];
        $errors = [];
        foreach (array_slice(Excel::toArray(new class {}, $request->file('file'))[0] ?? [], 1, preserve_keys: true) as $i => $row) {
            $code = mb_strtolower(trim((string) ($row[ScoreSheetExport::CODE_COLUMN] ?? '')));
            $value = trim((string) ($row[ScoreSheetExport::SCORE_COLUMN] ?? ''));
            if ($code === '' && $value === '') {
                continue;
            }
            if (! isset($byCode[$code])) {
                $errors[] = ['line' => $i + 1, 'error' => __('exams.errors.unknown_code', ['code' => $code])];

                continue;
            }
            $cells[] = ['enrollment_id' => $byCode[$code], 'subject_id' => $subjectId, 'value' => $value === '' ? null : $value, 'line' => $i + 1];
        }

        $saved = 0;
        if ($cells) {
            foreach ($enter->handle($exam, $classroom, $cells, $request->user()) as $n => $result) {
                $result['ok'] ? $saved++ : $errors[] = ['line' => $cells[$n]['line'], 'error' => $result['error']];
            }
        }

        return back()->with($errors ? 'error' : 'success', __('exams.import_result', ['saved' => $saved, 'errors' => count($errors)]))
            ->with('score_import_errors', $errors);
    }

    /** The class, which must be one of this exam's (404 otherwise, like any record of another school). */
    private function classroom(Request $request, Exam $exam, ?int $id = null): Classroom
    {
        $classroom = $exam->classrooms()->where('classrooms.id', $id ?? $request->integer('classroom'))->first();
        abort_unless($classroom !== null, 404);

        return $classroom;
    }

    /**
     * Columns the user sees: managers and approvers every subject of the class; a teacher only theirs.
     * Each says whether this user may type in it now.
     *
     * @return Collection<int, covariant array<string, mixed>>
     */
    private function subjects(Request $request, Exam $exam, Classroom $classroom): Collection
    {
        $user = $request->user();
        $sheets = ExamSubjectStatus::where('exam_id', $exam->id)->where('classroom_id', $classroom->id)->get()->keyBy('subject_id');
        $curriculum = CurriculumSubject::forClassroom($classroom);
        $seesAll = $this->policy->approveSubjectScores($user, $exam, $classroom);
        $editable = [SubjectExamStatus::Draft, SubjectExamStatus::Returned];

        return Subject::whereIn('id', $sheets->keys())->get()
            ->sortBy(fn (Subject $s) => $curriculum->get($s->id)->sort ?? PHP_INT_MAX)
            ->map(function (Subject $s) use ($user, $exam, $classroom, $sheets, $curriculum, $editable) {
                $sheet = $sheets[$s->id];
                $mine = $this->policy->enterScores($user, $exam, $classroom, $s);

                return [
                    ...$s->only('id', 'code', 'name_km', 'name_en'),
                    'sheet_id' => $sheet->id,
                    'status' => $sheet->status->value,
                    'returned_reason' => $sheet->returned_reason,
                    'max_score' => rtrim(rtrim((string) $curriculum->get($s->id)?->max_score, '0'), '.') ?: null,
                    'mine' => $mine,
                    'can_edit' => $mine && in_array($sheet->status, $editable, true) && $curriculum->has($s->id),
                ];
            })
            ->filter(fn (array $s) => $seesAll || $s['mine']);
    }

    /** @param list<int> $subjectIds */
    private function authorizeSubjects(Request $request, Exam $exam, Classroom $classroom, array $subjectIds): void
    {
        foreach (Subject::whereKey(array_unique($subjectIds))->get() as $subject) {
            abort_unless($this->policy->enterScores($request->user(), $exam, $classroom, $subject), 403);
        }
        // A subject id from another school, or none at all, finds no Subject and is refused here.
        abort_unless(Subject::whereKey(array_unique($subjectIds))->count() === count(array_unique($subjectIds)), 403);
    }
}
