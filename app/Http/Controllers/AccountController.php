<?php

namespace App\Http\Controllers;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\People\Enums\TeacherStatus;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Models\School;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AccountController extends Controller
{
    public function dashboard(Request $request, CurrentSchool $current): Response
    {
        $school = $current->get();
        if (! $school) {
            return Inertia::render('Dashboard', [
                'hasSchool' => false,
                'teacherDashboard' => null,
                'adminDashboard' => null,
            ]);
        }

        $user = $request->user();
        $teacher = $user?->teacherProfile();
        $isTeacher = $user?->isTeacher() || $teacher !== null;

        $currentYear = AcademicYear::where('school_id', $school->id)->where('is_current', true)->first()
            ?? AcademicYear::where('school_id', $school->id)->orderByDesc('start_date')->first();

        $teacherDashboard = null;
        if ($isTeacher && $teacher && $currentYear) {
            $homeroomClasses = Classroom::where('school_id', $school->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('homeroom_teacher_id', $teacher->id)
                ->with(['gradeLevel:id,number,name_km,name_en', 'enrollments'])
                ->get()
                ->map(fn (Classroom $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'grade' => $c->gradeLevel ? (app()->getLocale() === 'en' ? $c->gradeLevel->name_en : $c->gradeLevel->name_km) : '',
                    'student_count' => $c->enrollments->count(),
                ]);

            $teachingAssignments = TeachingAssignment::where('school_id', $school->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('teacher_id', $teacher->id)
                ->with([
                    'classroom.gradeLevel:id,number,name_km,name_en',
                    'classroom.enrollments',
                    'subject:id,code,name_km,name_en',
                ])
                ->get()
                ->map(fn (TeachingAssignment $ta) => [
                    'id' => $ta->id,
                    'classroom_id' => $ta->classroom_id,
                    'classroom_name' => $ta->classroom?->name,
                    'grade' => $ta->classroom?->gradeLevel ? (app()->getLocale() === 'en' ? $ta->classroom->gradeLevel->name_en : $ta->classroom->gradeLevel->name_km) : '',
                    'student_count' => $ta->classroom?->enrollments->count() ?? 0,
                    'subject_id' => $ta->subject_id,
                    'subject_code' => $ta->subject?->code,
                    'subject_name' => app()->getLocale() === 'en' ? $ta->subject?->name_en : $ta->subject?->name_km,
                    'hours_per_week' => $ta->hours_per_week,
                ]);

            $assignedClassroomIds = $teachingAssignments->pluck('classroom_id')
                ->merge($homeroomClasses->pluck('id'))
                ->filter()
                ->unique()
                ->all();

            $openExams = Exam::where('school_id', $school->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('status', ExamStatus::Open)
                ->whereHas('classrooms', fn ($q) => $q->whereIn('classrooms.id', $assignedClassroomIds))
                ->with([
                    'term:id,name_km,name_en',
                    'subjectStatuses' => fn ($q) => $q->whereIn('classroom_id', $assignedClassroomIds),
                    'subjectStatuses.subject:id,code,name_km,name_en',
                    'subjectStatuses.classroom:id,name',
                ])
                ->orderBy('start_date')
                ->get()
                ->map(fn (Exam $e) => [
                    'id' => $e->id,
                    'name' => $e->name,
                    'type' => $e->type->value,
                    'month' => $e->month,
                    'start_date' => $e->start_date?->format('Y-m-d'),
                    'end_date' => $e->end_date?->format('Y-m-d'),
                    'subjects' => $e->subjectStatuses->map(fn (ExamSubjectStatus $ss) => [
                        'id' => $ss->id,
                        'classroom_id' => $ss->classroom_id,
                        'classroom_name' => $ss->classroom?->name,
                        'subject_id' => $ss->subject_id,
                        'subject_name' => app()->getLocale() === 'en' ? $ss->subject?->name_en : $ss->subject?->name_km,
                        'status' => $ss->status->value,
                        'returned_reason' => $ss->returned_reason,
                    ]),
                ]);

            $teacherDashboard = [
                'current_year_name' => $currentYear->name,
                'homeroom_classes' => $homeroomClasses,
                'teaching_assignments' => $teachingAssignments,
                'open_exams' => $openExams,
            ];
        }

        $adminDashboard = null;
        if (! $teacherDashboard && $currentYear) {
            $adminDashboard = [
                'current_year_name' => $currentYear->name,
                'classrooms_count' => Classroom::where('school_id', $school->id)->where('academic_year_id', $currentYear->id)->count(),
                'teachers_count' => Teacher::where('school_id', $school->id)->where('status', TeacherStatus::Active)->count(),
                'students_count' => Enrollment::where('school_id', $school->id)->where('academic_year_id', $currentYear->id)->count(),
                'open_exams_count' => Exam::where('school_id', $school->id)->where('academic_year_id', $currentYear->id)->where('status', ExamStatus::Open)->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'hasSchool' => true,
            'teacherDashboard' => $teacherDashboard,
            'adminDashboard' => $adminDashboard,
        ]);
    }

    /** Works for guests too, so the login page can switch language. */
    public function locale(Request $request): RedirectResponse
    {
        $locale = $request->validate(['locale' => ['required', Rule::in(SetLocale::LOCALES)]])['locale'];

        $request->session()->put('locale', $locale);
        $request->user()?->forceFill(['locale' => $locale])->save();

        return back();
    }

    public function switchSchool(Request $request): SymfonyResponse
    {
        /** @var User $user */
        $user = $request->user();
        $school = School::find($request->integer('school_id'));

        abort_unless($school && $user->canAccessSchool($school), 404);
        $request->session()->put('school_id', $school->id);

        // Full page load: the school's brand colour is set on <html>.
        return Inertia::location(route('dashboard'));
    }

    /** Two-factor setup and password change. */
    public function security(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $pending = $user->two_factor_secret && ! $user->hasConfirmedTwoFactor();

        return Inertia::render('Security', [
            'twoFactor' => [
                'required' => $user->needsTwoFactor(),
                'enabled' => $user->two_factor_secret !== null,
                'confirmed' => $user->hasConfirmedTwoFactor(),
                'qrSvg' => $pending ? $user->twoFactorQrCodeSvg() : null,
                'setupKey' => $pending ? decrypt($user->two_factor_secret) : null,
                'recoveryCodes' => $user->hasConfirmedTwoFactor() ? $user->recoveryCodes() : [],
            ],
        ]);
    }
}
