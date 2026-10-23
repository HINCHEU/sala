<?php

namespace App\Http\Controllers\Academic;

use App\Domain\Academic\Enums\Shift;
use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Room;
use App\Domain\Academic\Models\Track;
use App\Domain\People\Enums\TeacherStatus;
use App\Domain\People\Models\Teacher;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ClassroomRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        $years = AcademicYear::orderByDesc('start_date')->get(['id', 'name', 'is_current']);
        $yearId = $request->integer('year') ?: ($years->firstWhere('is_current', true) ?? $years->first())?->id;

        return Inertia::render('Academic/Classrooms', [
            'years' => $years,
            'yearId' => $yearId,
            'classrooms' => Classroom::with('gradeLevel:id,number')->where('academic_year_id', $yearId)->get()
                ->sortBy(fn (Classroom $c) => sprintf('%02d-%s', $c->gradeLevel->number, $c->name))->values(),
            'grades' => GradeLevel::orderBy('number')->get(['id', 'name_km', 'name_en', 'has_tracks']),
            'tracks' => Track::orderBy('code')->get(['id', 'name_km', 'name_en']),
            'rooms' => Room::orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::where('status', TeacherStatus::Active)->orderBy('name_km')->get(['id', 'name_km']),
            'shifts' => array_column(Shift::cases(), 'value'),
        ]);
    }

    public function store(ClassroomRequest $request, \App\Domain\Teaching\Actions\SyncHomeroomTeacherRole $syncHomeroomRole): RedirectResponse
    {
        $classroom = Classroom::create($request->validated());
        if ($classroom->homeroom_teacher_id) {
            $syncHomeroomRole->handle($classroom->school, $classroom->homeroom_teacher_id);
        }

        return back()->with('success', __('academic.saved'));
    }

    public function update(ClassroomRequest $request, Classroom $classroom, \App\Domain\Teaching\Actions\SyncHomeroomTeacherRole $syncHomeroomRole): RedirectResponse
    {
        $oldTeacherId = $classroom->homeroom_teacher_id;
        $classroom->update($request->validated());
        $newTeacherId = $classroom->homeroom_teacher_id;

        if ($oldTeacherId !== $newTeacherId) {
            $syncHomeroomRole->handle($classroom->school, $oldTeacherId);
            $syncHomeroomRole->handle($classroom->school, $newTeacherId);
        }

        return back()->with('success', __('academic.saved'));
    }

    public function destroy(Classroom $classroom, \App\Domain\Teaching\Actions\SyncHomeroomTeacherRole $syncHomeroomRole): RedirectResponse
    {
        $teacherId = $classroom->homeroom_teacher_id;
        $school = $classroom->school;
        $response = $this->destroyUnlessUsed($classroom);

        if ($teacherId && ! Classroom::where('id', $classroom->id)->exists()) {
            $syncHomeroomRole->handle($school, $teacherId);
        }

        return $response;
    }
}
