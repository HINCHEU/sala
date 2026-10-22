<?php

namespace App\Http\Controllers\People;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Actions\EnrollStudents;
use App\Domain\People\Actions\MoveStudents;
use App\Domain\People\Actions\UnenrollStudents;
use App\Domain\People\Enums\StudentStatus;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Student;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\EnrollmentRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The enrollment board: one class's list, the students with no class yet, and moves between classes. */
class EnrollmentController extends Controller
{
    public const CANDIDATE_LIMIT = 100;

    public function index(Request $request): Response
    {
        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $yearId = $request->integer('year') ?: ($years->firstWhere('is_current', true) ?? $years->first())?->id;

        $classrooms = Classroom::where('academic_year_id', $yearId)->with('gradeLevel:id,number')->withCount('enrollments')->get()
            ->sortBy(fn (Classroom $c) => sprintf('%02d-%s', $c->gradeLevel->number, $c->name))->values();
        $classroom = $classrooms->firstWhere('id', $request->integer('classroom')) ?? $classrooms->first();
        $q = trim((string) $request->string('q'));

        return Inertia::render('People/Enrollment', [
            'years' => $years,
            'yearId' => $yearId,
            'classrooms' => $classrooms->map(fn (Classroom $c) => [
                ...$c->only('id', 'name', 'capacity'), 'count' => $c->enrollments_count,
            ]),
            'classroomId' => $classroom?->id,
            'enrolled' => $classroom ? Enrollment::where('classroom_id', $classroom->id)->with('student')
                ->orderBy('roll_no')->get()
                ->map(fn (Enrollment $e) => [
                    'id' => $e->id,
                    'roll_no' => $e->roll_no,
                    'student_id' => $e->student_id,
                    'student_code' => $e->student->student_code,
                    'name_km' => $e->student->nameKm(),
                    'name_en' => $e->student->nameEn(),
                    'gender' => $e->student->gender->value,
                ]) : [],
            // Active students with no class this year, the ones the board can add.
            'candidates' => $yearId ? Student::where('status', StudentStatus::Active)
                ->whereDoesntHave('enrollments', fn (Builder $e) => $e->where('academic_year_id', $yearId))
                ->search($q)
                ->orderByKhmerName()
                ->limit(self::CANDIDATE_LIMIT)
                ->get()
                ->map(fn (Student $s) => [
                    'id' => $s->id,
                    'student_code' => $s->student_code,
                    'name_km' => $s->nameKm(),
                    'name_en' => $s->nameEn(),
                    'gender' => $s->gender->value,
                ]) : [],
            'q' => $q,
            'candidateLimit' => self::CANDIDATE_LIMIT,
        ]);
    }

    public function store(EnrollmentRequest $request, EnrollStudents $enroll): RedirectResponse
    {
        $enroll->handle($request->classroom(), $request->studentIds());

        return back()->with('success', __('people.enrolled'));
    }

    public function move(EnrollmentRequest $request, MoveStudents $move): RedirectResponse
    {
        $move->handle($request->classroom(), $request->enrollments());

        return back()->with('success', __('people.moved'));
    }

    public function destroy(EnrollmentRequest $request, UnenrollStudents $unenroll): RedirectResponse
    {
        try {
            $unenroll->handle($request->enrollments());
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return back()->with('error', __('people.enrollment_in_use'));
        }

        return back()->with('success', __('people.unenrolled'));
    }
}
