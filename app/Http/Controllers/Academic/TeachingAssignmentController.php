<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Subject;
use App\Domain\People\Enums\TeacherStatus;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Enums\MembershipStatus;
use App\Domain\Teaching\Actions\CopyTeachingAssignments;
use App\Domain\Teaching\Actions\SaveApprovalAssignments;
use App\Domain\Teaching\Actions\SaveTeachingAssignments;
use App\Domain\Teaching\Models\ApprovalAssignment;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request, CurrentSchool $currentSchool): Response
    {
        $school = $currentSchool->get();
        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $yearId = $request->integer('year') ?: ($years->firstWhere('is_current', true) ?? $years->first())?->id;

        $classrooms = Classroom::with([
            'gradeLevel:id,number,name_km,name_en,has_tracks',
            'track:id,code,name_km,name_en',
            'homeroomTeacher:id,name_km,name_en',
        ])
            ->where('academic_year_id', $yearId)
            ->get()
            ->sortBy(fn (Classroom $c) => sprintf('%02d-%s', $c->gradeLevel->number, $c->name))
            ->values();

        $selectedClassroomId = $request->integer('classroom_id') ?: $classrooms->first()?->id;
        $selectedClassroom = $classrooms->firstWhere('id', $selectedClassroomId);

        $curriculumSubjects = collect();
        $assignments = collect();

        if ($selectedClassroom) {
            $curriculumSubjects = CurriculumSubject::with('subject:id,code,name_km,name_en')
                ->where('academic_year_id', $yearId)
                ->where('grade_level_id', $selectedClassroom->grade_level_id)
                ->where(function ($q) use ($selectedClassroom) {
                    $q->whereNull('track_id');
                    if ($selectedClassroom->track_id) {
                        $q->orWhere('track_id', $selectedClassroom->track_id);
                    }
                })
                ->orderBy('sort')
                ->get()
                ->map(fn (CurriculumSubject $cs) => [
                    'id' => $cs->subject_id,
                    'code' => $cs->subject->code,
                    'name_km' => $cs->subject->name_km,
                    'name_en' => $cs->subject->name_en,
                    'max_score' => $cs->max_score,
                    'coefficient' => $cs->coefficient,
                ]);

            if ($curriculumSubjects->isEmpty()) {
                $curriculumSubjects = Subject::orderBy('code')->get(['id', 'code', 'name_km', 'name_en']);
            }

            $assignments = TeachingAssignment::where('classroom_id', $selectedClassroom->id)
                ->get(['id', 'subject_id', 'teacher_id', 'hours_per_week']);
        }

        $teachers = Teacher::where('status', TeacherStatus::Active)
            ->with('subjects:id')
            ->orderBy('name_km')
            ->get(['id', 'staff_code', 'name_km', 'name_en'])
            ->map(fn (Teacher $t) => [
                'id' => $t->id,
                'staff_code' => $t->staff_code,
                'name_km' => $t->name_km,
                'name_en' => $t->name_en,
                'subject_ids' => $t->subjects->pluck('id')->all(),
            ]);

        $approvalAssignments = ApprovalAssignment::where('academic_year_id', $yearId)->get();
        $grades = GradeLevel::orderBy('number')->get(['id', 'number', 'name_km', 'name_en']);

        $staffUsers = $school ? $school->users()
            ->wherePivot('status', MembershipStatus::Active->value)
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']) : collect();

        return Inertia::render('Academic/Teaching', [
            'years' => $years,
            'yearId' => $yearId,
            'classrooms' => $classrooms,
            'selectedClassroomId' => $selectedClassroomId,
            'selectedClassroom' => $selectedClassroom,
            'curriculumSubjects' => $curriculumSubjects,
            'assignments' => $assignments,
            'teachers' => $teachers,
            'approvalAssignments' => $approvalAssignments,
            'grades' => $grades,
            'staffUsers' => $staffUsers,
        ]);
    }

    public function update(
        Request $request,
        Classroom $classroom,
        SaveTeachingAssignments $saveAction
    ): RedirectResponse {
        $validated = $request->validate([
            'assignments' => ['present', 'array'],
            'assignments.*.subject_id' => ['required', 'integer'],
            'assignments.*.teacher_id' => ['nullable', 'integer'],
            'assignments.*.hours_per_week' => ['nullable', 'integer', 'min:0', 'max:40'],
        ]);

        $saveAction->handle($classroom, $validated['assignments']);

        return back()->with('success', __('academic.saved'));
    }

    public function copy(
        Request $request,
        CopyTeachingAssignments $copyAction,
        CurrentSchool $currentSchool
    ): RedirectResponse {
        $validated = $request->validate([
            'target_year_id' => ['required', 'integer'],
            'source_year_id' => ['required', 'integer', 'different:target_year_id'],
        ]);

        $school = $currentSchool->get();
        $targetYear = AcademicYear::findOrFail($validated['target_year_id']);
        $sourceYear = AcademicYear::findOrFail($validated['source_year_id']);

        $count = $copyAction->handle($school, $targetYear, $sourceYear);

        return back()->with('success', __('academic.teaching_copied', ['count' => $count]));
    }

    public function updateApprovals(
        Request $request,
        SaveApprovalAssignments $saveApprovals,
        CurrentSchool $currentSchool
    ): RedirectResponse {
        $validated = $request->validate([
            'year_id' => ['required', 'integer'],
            'approvals' => ['required', 'array'],
            'approvals.*.grade_level_id' => ['required', 'integer'],
            'approvals.*.approver_type' => ['required', 'in:homeroom,role,user'],
            'approvals.*.role' => ['nullable', 'string', 'max:50'],
            'approvals.*.user_id' => ['nullable', 'integer'],
        ]);

        $school = $currentSchool->get();
        $year = AcademicYear::findOrFail($validated['year_id']);

        $saveApprovals->handle($school, $year, $validated['approvals']);

        return back()->with('success', __('academic.saved'));
    }
}
