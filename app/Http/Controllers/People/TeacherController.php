<?php

namespace App\Http\Controllers\People;

use App\Domain\Academic\Models\Subject;
use App\Domain\People\Actions\SaveTeacher;
use App\Domain\People\Enums\EmploymentType;
use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\TeacherStatus;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Actions\InviteMember;
use App\Domain\School\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\TeacherRequest;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TeacherStatus::class)],
        ]);
        $q = trim($filters['q'] ?? '');
        $like = '%'.addcslashes($q, '%_\\').'%';
        $status = $filters['status'] ?? TeacherStatus::Active->value;

        $teachers = Teacher::query()
            ->where('status', $status)
            ->when($q !== '', fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('name_km', 'like', $like)->orWhere('name_en', 'like', $like)
                ->orWhere('staff_code', 'like', $like)->orWhere('phone', 'like', $like)))
            ->with('subjects:id,code')
            ->orderBy('name_km')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Teacher $t) => [
                ...$t->only('id', 'staff_code', 'name_km', 'name_en', 'phone'),
                'employment_type' => $t->employment_type->value,
                'subjects' => $t->subjects->pluck('code'),
                'has_login' => $t->user_id !== null,
            ]);

        return Inertia::render('People/Teachers/Index', [
            'teachers' => $teachers,
            'filters' => ['q' => $q, 'status' => $status],
            'statuses' => array_column(TeacherStatus::cases(), 'value'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('People/Teachers/Form', ['teacher' => null, ...$this->options()]);
    }

    public function store(TeacherRequest $request, SaveTeacher $save): RedirectResponse
    {
        $teacher = $save->handle(null, $request->teacher(), $request->subjectIds());

        return redirect()->route('people.teachers.edit', $teacher)->with('success', __('people.saved'));
    }

    public function edit(Teacher $teacher): Response
    {
        $teacher->load('subjects:id', 'user:id,name');

        return Inertia::render('People/Teachers/Form', [
            'teacher' => [
                ...$teacher->only('id', 'staff_code', 'name_km', 'name_en', 'phone', 'email', 'qualification'),
                'gender' => $teacher->gender->value,
                'employment_type' => $teacher->employment_type->value,
                'status' => $teacher->status->value,
                'dob' => $teacher->dob?->toDateString(),
                'hired_at' => $teacher->hired_at?->toDateString(),
                'subject_ids' => $teacher->subjects->modelKeys(),
                'login_name' => $teacher->user?->name,
            ],
            ...$this->options(),
        ]);
    }

    public function update(TeacherRequest $request, Teacher $teacher, SaveTeacher $save): RedirectResponse
    {
        $save->handle($teacher, $request->teacher(), $request->subjectIds());

        return back()->with('success', __('people.saved'));
    }

    /** A sign-in link for the teacher, to their email or phone. Accepting it links the login to this record. */
    public function invite(Request $request, Teacher $teacher, InviteMember $invite, CurrentSchool $current): RedirectResponse
    {
        if ($teacher->user_id) {
            return back()->with('error', __('people.has_login'));
        }
        if (! $teacher->email && ! $teacher->phone) {
            return back()->with('error', __('people.needs_contact'));
        }

        $link = $invite->handle($current->get(), Role::SubjectTeacher, $teacher->email, $teacher->phone, $request->user(), $teacher);

        return back()->with('invite_link', $link);
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'subjects' => Subject::orderBy('name_km')->get(['id', 'code', 'name_km', 'name_en']),
            'genders' => array_column(Gender::cases(), 'value'),
            'employmentTypes' => array_column(EmploymentType::cases(), 'value'),
            'statuses' => array_column(TeacherStatus::cases(), 'value'),
        ];
    }
}
