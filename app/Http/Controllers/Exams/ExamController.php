<?php

namespace App\Http\Controllers\Exams;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Term;
use App\Domain\Exams\Actions\CreateExam;
use App\Domain\Exams\Actions\TransitionExamStatus;
use App\Domain\Exams\Actions\TransitionSubjectExamStatus;
use App\Domain\Exams\Actions\UpdateExam;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\ExamType;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Exams\Models\Score;
use App\Domain\Exams\Policies\ExamPolicy;
use App\Domain\People\Models\Enrollment;
use App\Domain\School\Enums\Permission;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\ExamRequest;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function index(Request $request, CurrentSchool $currentSchool): Response
    {
        Gate::authorize('viewAny', Exam::class);
        $user = $request->user();

        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $yearId = $request->integer('year') ?: ($years->firstWhere('is_current', true) ?? $years->first())?->id;

        $exams = Exam::with([
            'academicYear:id,name',
            'term:id,name_km,name_en',
            'classrooms:id,name,grade_level_id',
            'classrooms.gradeLevel:id,number',
            'subjectStatuses',
        ])
            ->where('academic_year_id', $yearId)
            ->orderByDesc('start_date')
            ->get()
            ->filter(fn (Exam $exam) => $user->can('view', $exam))
            ->values()
            ->map(function (Exam $exam) {
                $statuses = $exam->subjectStatuses;

                return [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'type' => $exam->type->value,
                    'month' => $exam->month,
                    'start_date' => $exam->start_date?->format('Y-m-d'),
                    'end_date' => $exam->end_date?->format('Y-m-d'),
                    'status' => $exam->status->value,
                    'term' => $exam->term ? [
                        'id' => $exam->term->id,
                        'name_km' => $exam->term->name_km,
                        'name_en' => $exam->term->name_en,
                    ] : null,
                    'classrooms_count' => $exam->classrooms->count(),
                    'subjects_total' => $statuses->count(),
                    'subjects_approved' => $statuses->where('status', SubjectExamStatus::Approved)->count(),
                    'subjects_submitted' => $statuses->where('status', SubjectExamStatus::Submitted)->count(),
                    'subjects_draft' => $statuses->where('status', SubjectExamStatus::Draft)->count(),
                    'subjects_returned' => $statuses->where('status', SubjectExamStatus::Returned)->count(),
                ];
            });

        return Inertia::render('Exams/Index', [
            'years' => $years,
            'yearId' => $yearId,
            'exams' => $exams,
        ]);
    }

    public function create(Request $request): Response
    {
        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $yearId = $request->integer('year') ?: ($years->firstWhere('is_current', true) ?? $years->first())?->id;

        $terms = Term::where('academic_year_id', $yearId)->orderBy('sort')->get(['id', 'academic_year_id', 'name_km', 'name_en']);
        $grades = GradeLevel::with(['classrooms' => fn ($q) => $q->where('academic_year_id', $yearId)->orderBy('name')])
            ->orderBy('number')
            ->get(['id', 'number', 'name_km', 'name_en']);

        return Inertia::render('Exams/Form', [
            'exam' => null,
            'years' => $years,
            'yearId' => $yearId,
            'terms' => $terms,
            'grades' => $grades,
            'types' => array_column(ExamType::cases(), 'value'),
        ]);
    }

    public function store(ExamRequest $request, CreateExam $createAction, CurrentSchool $currentSchool): RedirectResponse
    {
        $exam = $createAction->handle(
            $currentSchool->get(),
            $request->validated(),
            $request->input('classroom_ids', [])
        );

        return redirect()->route('exams.show', $exam->id)->with('success', __('exams.created'));
    }

    public function show(Exam $exam, Request $request, ExamPolicy $policy): Response
    {
        Gate::authorize('view', $exam);

        $exam->load([
            'academicYear:id,name',
            'term:id,name_km,name_en',
            'classrooms.gradeLevel:id,number,name_km,name_en',
            'classrooms.track:id,code,name_km,name_en',
            'classrooms.homeroomTeacher:id,name_km,name_en',
            'classrooms.teachingAssignments.teacher:id,name_km,name_en',
            'subjectStatuses.subject:id,code,name_km,name_en',
            'subjectStatuses.classroom:id,name,grade_level_id',
            'subjectStatuses.submittedBy:id,name',
            'subjectStatuses.approvedBy:id,name',
        ]);

        $user = $request->user();
        $teacher = $user->teacherProfile();

        // Progress per sheet: students in the class, and cells filled for that subject.
        $students = Enrollment::whereIn('classroom_id', $exam->classrooms->modelKeys())
            ->selectRaw('classroom_id, count(*) as total')->groupBy('classroom_id')->pluck('total', 'classroom_id');
        $filled = Score::where('exam_id', $exam->id)
            ->join('enrollments', 'enrollments.id', '=', 'scores.enrollment_id')
            ->selectRaw('enrollments.classroom_id, scores.subject_id, count(*) as total')
            ->groupBy('enrollments.classroom_id', 'scores.subject_id')->toBase()->get() // plain rows; the school scope still applies
            ->mapWithKeys(fn ($row) => ["{$row->classroom_id}-{$row->subject_id}" => (int) $row->total]);
        $classrooms = $exam->classrooms->keyBy('id');
        $approver = $classrooms->map(fn (Classroom $c) => $policy->approveSubjectScores($user, $exam, $c));
        $editable = [SubjectExamStatus::Draft, SubjectExamStatus::Returned];

        return Inertia::render('Exams/Show', [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'type' => $exam->type->value,
                'month' => $exam->month,
                'start_date' => $exam->start_date?->format('Y-m-d'),
                'end_date' => $exam->end_date?->format('Y-m-d'),
                'status' => $exam->status->value,
                'description' => $exam->description,
                'academic_year' => $exam->academicYear ? [
                    'id' => $exam->academicYear->id,
                    'name' => $exam->academicYear->name,
                ] : null,
                'term' => $exam->term ? [
                    'id' => $exam->term->id,
                    'name_km' => $exam->term->name_km,
                    'name_en' => $exam->term->name_en,
                ] : null,
            ],
            'classrooms' => $exam->classrooms->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'grade_level' => $c->gradeLevel ? [
                    'id' => $c->gradeLevel->id,
                    'number' => $c->gradeLevel->number,
                    'name_km' => $c->gradeLevel->name_km,
                    'name_en' => $c->gradeLevel->name_en,
                ] : null,
                'homeroom_teacher' => $c->homeroomTeacher ? [
                    'id' => $c->homeroomTeacher->id,
                    'name_km' => $c->homeroomTeacher->name_km,
                    'name_en' => $c->homeroomTeacher->name_en,
                ] : null,
                'teaching_assignments' => $c->teachingAssignments->map(fn (TeachingAssignment $ta) => [
                    'subject_id' => $ta->subject_id,
                    'teacher_id' => $ta->teacher_id,
                    'teacher_name' => $ta->teacher?->name_km,
                ]),
            ]),
            'subjectStatuses' => $exam->subjectStatuses->map(fn (ExamSubjectStatus $ss) => [
                'id' => $ss->id,
                'exam_id' => $ss->exam_id,
                'classroom_id' => $ss->classroom_id,
                'subject_id' => $ss->subject_id,
                'subject' => $ss->subject ? [
                    'id' => $ss->subject->id,
                    'code' => $ss->subject->code,
                    'name_km' => $ss->subject->name_km,
                    'name_en' => $ss->subject->name_en,
                ] : null,
                'status' => $ss->status->value,
                'submitted_by' => $ss->submittedBy?->name,
                'submitted_at' => $ss->submitted_at?->format('Y-m-d H:i'),
                'approved_by' => $ss->approvedBy?->name,
                'approved_at' => $ss->approved_at?->format('Y-m-d H:i'),
                'returned_reason' => $ss->returned_reason,
                'students' => (int) ($students[$ss->classroom_id] ?? 0),
                'filled' => $filled["{$ss->classroom_id}-{$ss->subject_id}"] ?? 0,
                // Decided here, from the policy, so the page never guesses who may do what.
                // A class taken off the exam after it opened keeps its sheets, but nobody acts on them.
                'can_enter' => $classrooms->has($ss->classroom_id) && in_array($ss->status, $editable, true)
                    && $policy->enterScores($user, $exam, $classrooms[$ss->classroom_id], $ss->subject),
                'can_approve' => $exam->isOpen() && $ss->status === SubjectExamStatus::Submitted && ($approver[$ss->classroom_id] ?? false),
                'can_view_scores' => $classrooms->has($ss->classroom_id) && $policy->viewScores($user, $exam, $classrooms[$ss->classroom_id]),
            ]),
            'canManage' => $user->can(Permission::ManageExams->value),
            'teacherId' => $teacher?->id,
        ]);
    }

    public function edit(Exam $exam, Request $request): Response
    {
        abort_if($exam->isLocked(), 403, __('exams.errors.locked_cannot_be_edited'));

        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $yearId = $exam->academic_year_id;

        $terms = Term::where('academic_year_id', $yearId)->orderBy('sort')->get(['id', 'academic_year_id', 'name_km', 'name_en']);
        $grades = GradeLevel::with(['classrooms' => fn ($q) => $q->where('academic_year_id', $yearId)->orderBy('name')])
            ->orderBy('number')
            ->get(['id', 'number', 'name_km', 'name_en']);

        return Inertia::render('Exams/Form', [
            'exam' => [
                'id' => $exam->id,
                'academic_year_id' => $exam->academic_year_id,
                'term_id' => $exam->term_id,
                'name' => $exam->name,
                'type' => $exam->type->value,
                'month' => $exam->month,
                'start_date' => $exam->start_date?->format('Y-m-d'),
                'end_date' => $exam->end_date?->format('Y-m-d'),
                'description' => $exam->description,
                'classroom_ids' => $exam->classrooms()->pluck('classrooms.id')->all(),
            ],
            'years' => $years,
            'yearId' => $yearId,
            'terms' => $terms,
            'grades' => $grades,
            'types' => array_column(ExamType::cases(), 'value'),
        ]);
    }

    public function update(ExamRequest $request, Exam $exam, UpdateExam $updateAction): RedirectResponse
    {
        $updateAction->handle($exam, $request->validated(), $request->input('classroom_ids', []));

        return redirect()->route('exams.show', $exam->id)->with('success', __('exams.saved'));
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        abort_unless($exam->status === ExamStatus::Draft, 403, __('exams.errors.only_draft_can_be_deleted'));

        // An exam moved back to draft may already hold scores; their keys refuse the delete.
        if (Score::where('exam_id', $exam->id)->exists()) {
            return back()->with('error', __('exams.errors.has_scores'));
        }

        $exam->delete();

        return redirect()->route('exams.index')->with('success', __('exams.deleted'));
    }

    public function transition(Request $request, Exam $exam, TransitionExamStatus $transitionAction): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ExamStatus::class)],
        ]);

        $transitionAction->handle($exam, ExamStatus::from($validated['status']));

        return back()->with('success', __('exams.status_updated'));
    }

    public function transitionSubject(
        Request $request,
        Exam $exam,
        ExamSubjectStatus $subjectStatus,
        TransitionSubjectExamStatus $transitionSubjectAction
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(SubjectExamStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $target = SubjectExamStatus::from($validated['status']);
        $policy = app(ExamPolicy::class);
        $classroom = $subjectStatus->classroom;

        abort_unless((int) $subjectStatus->exam_id === $exam->id && $classroom && $subjectStatus->subject, 404);
        if (! $exam->isOpen()) {
            throw ValidationException::withMessages(['status' => __('exams.errors.not_open_for_scores')]);
        }

        // Approving or returning is the approver's; submitting or taking back is whoever enters the scores.
        if (in_array($target, [SubjectExamStatus::Approved, SubjectExamStatus::Returned], true)) {
            abort_unless($policy->approveSubjectScores($request->user(), $exam, $classroom), 403, __('exams.errors.not_authorized_to_approve'));
        } else {
            abort_unless($policy->enterScores($request->user(), $exam, $classroom, $subjectStatus->subject), 403);
        }

        $transitionSubjectAction->handle(
            $subjectStatus,
            $target,
            $request->user(),
            $validated['reason'] ?? null
        );

        return back()->with('success', __('exams.subject_status_updated'));
    }
}
