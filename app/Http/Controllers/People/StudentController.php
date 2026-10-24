<?php

namespace App\Http\Controllers\People;

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Actions\SaveStudent;
use App\Domain\People\Enums\DocumentType;
use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\IdPoorLevel;
use App\Domain\People\Enums\Relationship;
use App\Domain\People\Enums\StudentStatus;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\People\Provinces;
use App\Domain\Results\Models\ExamResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\StudentRequest;
use App\Support\Files\StoredFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'classroom' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
        ]);
        $status = $filters['status'] ?? StudentStatus::Active->value;
        $year = AcademicYear::current();

        $students = Student::query()
            ->search($filters['q'] ?? null)
            ->where('status', $status)
            ->when($filters['classroom'] ?? null, fn (Builder $q, int $id) => $q->whereHas('enrollments', fn (Builder $e) => $e->where('classroom_id', $id)))
            ->with([
                'enrollments' => fn ($q) => $q->where('academic_year_id', $year?->id)->with('classroom:id,name'),
                'guardians' => fn ($q) => $q->wherePivot('is_primary_contact', true),
            ])
            ->orderByKhmerName()
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Student $s) => [
                'id' => $s->id,
                'student_code' => $s->student_code,
                'name_km' => $s->nameKm(),
                'name_en' => $s->nameEn(),
                'gender' => $s->gender->value,
                'classroom' => $s->enrollments->first()?->classroom->name,
                'guardian_phone' => $s->guardians->first()?->phone,
            ]);

        return Inertia::render('People/Students/Index', [
            'students' => $students,
            'filters' => ['q' => $filters['q'] ?? '', 'classroom' => $filters['classroom'] ?? null, 'status' => $status],
            'classrooms' => $year ? Classroom::where('academic_year_id', $year->id)->orderBy('name')->get(['id', 'name']) : [],
            'statuses' => array_column(StudentStatus::cases(), 'value'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('People/Students/Form', ['student' => null, ...$this->options()]);
    }

    public function store(StudentRequest $request, SaveStudent $save): RedirectResponse
    {
        $student = $save->handle(null, $request->student(), $request->file('photo'));

        return redirect()->route('people.students.show', $student)->with('success', __('people.saved'));
    }

    public function show(Student $student): Response
    {
        $student->load(['photo', 'documents', 'guardians', 'enrollments' => fn ($q) => $q->with('academicYear:id,name,start_date', 'classroom:id,name')]);

        return Inertia::render('People/Students/Show', [
            'student' => [
                ...$this->fields($student),
                'id' => $student->id,
                'name_km' => $student->nameKm(),
                'name_en' => $student->nameEn(),
                'photo_url' => $student->photo?->url(),
            ],
            'guardians' => $student->guardians->map(fn (Guardian $g) => [
                ...$g->only('id', 'name_km', 'name_en', 'occupation', 'phone', 'address'),
                'gender' => $g->gender?->value,
                'relationship' => $g->pivot->relationship,
                'is_primary_contact' => $g->pivot->is_primary_contact,
                'can_pickup' => $g->pivot->can_pickup,
            ]),
            'enrollments' => $student->enrollments->sortByDesc(fn (Enrollment $e) => $e->academicYear->start_date)->values()
                ->map(fn (Enrollment $e) => [
                    'id' => $e->id,
                    'year' => $e->academicYear->name,
                    'classroom' => $e->classroom->name,
                    'roll_no' => $e->roll_no,
                    'status' => $e->status,
                ]),
            'documents' => $student->documents->map(fn (StoredFile $f) => [
                'id' => $f->id,
                'type' => $f->collection,
                'name' => $f->original_name,
                'size' => $f->size,
                'url' => $f->url(),
                'created_at' => $f->created_at?->toDateString(),
            ]),
            'relationships' => array_column(Relationship::cases(), 'value'),
            'genders' => array_column(Gender::cases(), 'value'),
            'documentTypes' => array_column(DocumentType::cases(), 'value'),
            // Every locked exam's current result, newest first.
            'results' => ExamResult::whereIn('enrollment_id', $student->enrollments->modelKeys())
                ->join('exams', 'exams.id', '=', 'exam_results.exam_id')
                ->whereColumn('exam_results.version', 'exams.results_version')
                ->orderByDesc('exams.end_date')
                ->get(['exam_results.*', 'exams.name as exam_name', 'exams.end_date as exam_date'])
                ->map(fn (ExamResult $r) => [
                    'exam_id' => $r->exam_id,
                    'exam' => $r->getAttribute('exam_name'),
                    'date' => $r->getAttribute('exam_date'),
                    'average' => (float) $r->average,
                    'rank_class' => $r->rank_class,
                    'mention' => $r->mention,
                    'passed' => $r->passed,
                ]),
        ]);
    }

    public function edit(Student $student): Response
    {
        $student->load('photo');

        return Inertia::render('People/Students/Form', [
            'student' => [...$this->fields($student), 'id' => $student->id, 'photo_url' => $student->photo?->url()],
            ...$this->options(),
        ]);
    }

    public function update(StudentRequest $request, Student $student, SaveStudent $save): RedirectResponse
    {
        $save->handle($student, $request->student(), $request->file('photo'));

        return redirect()->route('people.students.show', $student)->with('success', __('people.saved'));
    }

    /** @return array<string, mixed> */
    private function fields(Student $student): array
    {
        return [
            ...$student->only(
                'student_code', 'last_name_km', 'first_name_km', 'last_name_en', 'first_name_en', 'birth_village',
                'birth_commune', 'birth_district', 'birth_province', 'address', 'birth_cert_no', 'id_poor_level', 'disability',
            ),
            'gender' => $student->gender->value,
            'status' => $student->status->value,
            'dob' => $student->dob?->toDateString(),
            'admitted_at' => $student->admitted_at?->toDateString(),
        ];
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'genders' => array_column(Gender::cases(), 'value'),
            'statuses' => array_column(StudentStatus::cases(), 'value'),
            'idPoorLevels' => array_column(IdPoorLevel::cases(), 'value'),
            'provinces' => collect(Provinces::ALL)->map(fn (string $en, string $km) => ['km' => $km, 'en' => $en])->values(),
        ];
    }
}
