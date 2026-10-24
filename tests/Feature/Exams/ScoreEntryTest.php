<?php

use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Actions\TransitionExamStatus;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\Exams\Models\Score;
use App\Domain\People\Actions\EnrollStudents;
use App\Domain\People\Models\Enrollment;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Enums\Role;
use App\Domain\Teaching\Enums\ApproverType;
use App\Domain\Teaching\Models\ApprovalAssignment;
use App\Domain\Teaching\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->school = makeSchool();
    $this->office = member($this->school, Role::AcademicOffice);
    $this->classes = classesFor($this->school);
    $this->sevenA = $this->classes['7A'];

    // Grade 7 curriculum: MATH out of 50, KHM out of 100.
    $this->math = Subject::where('code', 'MATH')->firstOrFail();
    $this->khmer = Subject::where('code', 'KHM')->firstOrFail();
    foreach ([[$this->math, 50], [$this->khmer, 100]] as $i => [$subject, $max]) {
        CurriculumSubject::create([
            'academic_year_id' => $this->sevenA->academic_year_id, 'grade_level_id' => $this->sevenA->grade_level_id,
            'subject_id' => $subject->id, 'max_score' => $max, 'coefficient' => 1, 'pass_score' => $max / 2, 'sort' => $i,
        ]);
    }

    $this->teacherUser = member($this->school, Role::SubjectTeacher);
    $this->teacher = teacherFor($this->teacherUser, 'ហេង សុភាព');
    TeachingAssignment::create([
        'academic_year_id' => $this->sevenA->academic_year_id, 'classroom_id' => $this->sevenA->id,
        'subject_id' => $this->math->id, 'teacher_id' => $this->teacher->id,
    ]);

    $students = [makeStudent('កែវ', 'មុនី'), makeStudent('ចាន់', 'សុភា'), makeStudent('សុខ', 'ដារ៉ា')];
    app(EnrollStudents::class)->handle($this->sevenA, array_map(fn ($s) => $s->id, $students));
    $this->enrollments = Enrollment::where('classroom_id', $this->sevenA->id)->with('student')->orderBy('roll_no')->get();

    $this->exam = openExam([$this->sevenA->id, $this->classes['7B']->id]);
});

/** A teacher record linked to $user, the way an accepted invitation links it. */
function teacherFor(User $user, string $name): Teacher
{
    $teacher = Teacher::create(['name_km' => $name, 'gender' => 'male', 'employment_type' => 'contract', 'status' => 'active']);
    $teacher->forceFill(['user_id' => $user->id])->save();

    return $teacher;
}

/** An exam for $classroomIds, opened, so its subject sheets exist. */
function openExam(array $classroomIds): Exam
{
    $exam = Exam::create([
        'academic_year_id' => test()->sevenA->academic_year_id, 'name' => 'ខែវិច្ឆិកា', 'type' => 'monthly',
        'month' => 11, 'start_date' => '2026-11-20', 'end_date' => '2026-11-25', 'status' => ExamStatus::Draft,
    ]);
    $exam->syncClassrooms($classroomIds);

    return app(TransitionExamStatus::class)->handle($exam, ExamStatus::Open);
}

function cells(int $subjectId, array $values): array
{
    return array_map(fn ($enrollment, $value) => ['enrollment_id' => $enrollment->id, 'subject_id' => $subjectId, 'value' => $value],
        test()->enrollments->take(count($values))->all(), $values);
}

function putScores(User $user, array $cells, ?int $classroomId = null)
{
    return test()->actingAs($user)->putJson('/exams/'.test()->exam->id.'/scores', [
        'classroom_id' => $classroomId ?? test()->sevenA->id, 'cells' => $cells,
    ]);
}

it('saves scores, absent and exempt marks, and clears a cell back to blank (not 0)', function () {
    putScores($this->teacherUser, cells($this->math->id, ['១២,5', 'a', 'E']))
        ->assertOk()
        ->assertJsonPath('cells.0.value', '12.5')
        ->assertJsonPath('cells.1.value', 'A')
        ->assertJsonPath('cells.2.value', 'E');

    expect(Score::where('enrollment_id', $this->enrollments[0]->id)->value('score'))->toBe('12.50')
        ->and(Score::where('enrollment_id', $this->enrollments[1]->id)->first()->mark->value)->toBe('absent');

    putScores($this->teacherUser, cells($this->math->id, ['']))->assertJsonPath('cells.0.value', null);
    expect(Score::where('enrollment_id', $this->enrollments[0]->id)->exists())->toBeFalse();
});

it('checks each cell on its own and still saves the good ones', function () {
    $sevenB = makeStudent('ពៅ', 'វណ្ណា');
    app(EnrollStudents::class)->handle($this->classes['7B'], [$sevenB->id]);
    $outsider = Enrollment::where('student_id', $sevenB->id)->value('id');

    $response = putScores($this->teacherUser, [
        ['enrollment_id' => $this->enrollments[0]->id, 'subject_id' => $this->math->id, 'value' => '51'],
        ['enrollment_id' => $this->enrollments[1]->id, 'subject_id' => $this->math->id, 'value' => '12.555'],
        ['enrollment_id' => $this->enrollments[2]->id, 'subject_id' => $this->math->id, 'value' => 'ten'],
        ['enrollment_id' => $outsider, 'subject_id' => $this->math->id, 'value' => '10'],
        ['enrollment_id' => $this->enrollments[2]->id, 'subject_id' => $this->math->id, 'value' => '50'],
    ])->assertOk();

    expect(collect($response->json('cells'))->pluck('error')->all())->toBe([
        __('exams.errors.above_max', ['max' => '50']),
        __('exams.errors.two_decimals'),
        __('exams.errors.not_a_score'),
        __('exams.errors.not_in_class'),
        null,
    ])->and(Score::count())->toBe(1);
});

it('refuses writes with 423 once the exam is not open', function (ExamStatus $status) {
    $this->exam->update(['status' => $status]);

    putScores($this->teacherUser, cells($this->math->id, ['10']))->assertStatus(423);
    expect(Score::count())->toBe(0);
})->with([ExamStatus::Draft, ExamStatus::Approved, ExamStatus::Locked, ExamStatus::Published]);

it('keeps a teacher without an assignment out of the grid and the save endpoint', function () {
    $other = member($this->school, Role::SubjectTeacher);
    teacherFor($other, 'គ្រូផ្សេង');

    $this->actingAs($other)->get("/exams/{$this->exam->id}/scores?classroom={$this->sevenA->id}")->assertForbidden();
    putScores($other, cells($this->math->id, ['10']))->assertForbidden();

    // The math teacher cannot type Khmer in the same class, nor open 7B where they teach nothing.
    putScores($this->teacherUser, cells($this->khmer->id, ['10']))->assertForbidden();
    $this->actingAs($this->teacherUser)->get("/exams/{$this->exam->id}/scores?classroom={$this->classes['7B']->id}")->assertForbidden();
    expect(Score::count())->toBe(0);
});

it('shows the assigned teacher only their subject, and the homeroom teacher every subject read-only', function () {
    $this->actingAs($this->teacherUser)->get("/exams/{$this->exam->id}/scores?classroom={$this->sevenA->id}")
        ->assertInertia(fn ($page) => $page->component('Exams/Scores')
            ->where('subjects', fn ($s) => collect($s)->pluck('code')->all() === ['MATH'] && $s[0]['can_edit'] === true)
            ->has('students', 3));

    $homeroomUser = member($this->school, Role::HomeroomTeacher);
    $this->sevenA->update(['homeroom_teacher_id' => teacherFor($homeroomUser, 'គ្រូបន្ទុក')->id]);

    $this->actingAs($homeroomUser)->get("/exams/{$this->exam->id}/scores?classroom={$this->sevenA->id}")
        ->assertInertia(fn ($page) => $page
            ->where('subjects', fn ($s) => collect($s)->pluck('code')->sort()->values()->all() === ['KHM', 'MATH']
                && collect($s)->every(fn ($subject) => $subject['can_edit'] === false)));
    putScores($homeroomUser, cells($this->math->id, ['10']))->assertForbidden();
});

it('seals a submitted subject, and opens it again when returned', function () {
    putScores($this->teacherUser, cells($this->math->id, ['10', '20', 'A']));
    $sheet = ExamSubjectStatus::where('exam_id', $this->exam->id)->where('classroom_id', $this->sevenA->id)->where('subject_id', $this->math->id)->firstOrFail();

    $this->actingAs($this->teacherUser)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'submitted'])
        ->assertSessionHasNoErrors();
    putScores($this->teacherUser, cells($this->math->id, ['11']))
        ->assertJsonPath('cells.0.ok', false)
        ->assertJsonPath('cells.0.value', '10');

    $this->actingAs($this->office)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'returned', 'reason' => 'ពិនិត្យលេខ ១ ឡើងវិញ']);
    putScores($this->teacherUser, cells($this->math->id, ['11']))->assertJsonPath('cells.0.value', '11');
});

it('submits a subject only when every student has a score or a mark', function () {
    $sheet = ExamSubjectStatus::where('exam_id', $this->exam->id)->where('classroom_id', $this->sevenA->id)->where('subject_id', $this->math->id)->firstOrFail();
    putScores($this->teacherUser, cells($this->math->id, ['10', '20']));

    $this->actingAs($this->teacherUser)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'submitted'])
        ->assertSessionHasErrors(['status' => __('exams.errors.incomplete', ['count' => 1])]);

    putScores($this->teacherUser, cells($this->math->id, ['10', '20', 'E']));
    $this->actingAs($this->teacherUser)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'submitted'])
        ->assertSessionHasNoErrors();
    expect($sheet->fresh()->status)->toBe(SubjectExamStatus::Submitted);
});

it('saves 45 students × 12 subjects without losing a cell', function () {
    $subjects = Subject::whereNotIn('id', [$this->math->id, $this->khmer->id])->limit(10)->get()->push($this->math, $this->khmer);
    foreach ($subjects->take(10) as $i => $subject) {
        CurriculumSubject::create([
            'academic_year_id' => $this->sevenA->academic_year_id, 'grade_level_id' => $this->sevenA->grade_level_id,
            'subject_id' => $subject->id, 'max_score' => 50, 'coefficient' => 1, 'pass_score' => 25, 'sort' => 10 + $i,
        ]);
    }
    $ids = collect(range(1, 42))->map(fn ($i) => makeStudent('សិស្ស', "ទី{$i}")->id)->all();
    app(EnrollStudents::class)->handle($this->sevenA, $ids);
    $exam = openExam([$this->sevenA->id]);
    $enrollments = Enrollment::where('classroom_id', $this->sevenA->id)->get();

    $cells = [];
    foreach ($enrollments as $e) {
        foreach ($subjects as $s) {
            $cells[] = ['enrollment_id' => $e->id, 'subject_id' => $s->id, 'value' => (string) (($e->id + $s->id) % 50)];
        }
    }
    // The office enters for everyone; two overlapping batches, as autosave sends them.
    foreach ([array_slice($cells, 0, 300), array_slice($cells, 250)] as $batch) {
        $this->actingAs($this->office)->putJson("/exams/{$exam->id}/scores", ['classroom_id' => $this->sevenA->id, 'cells' => $batch])
            ->assertOk()->assertJsonMissing(['ok' => false]);
    }

    expect($enrollments)->toHaveCount(45)
        ->and(Score::where('exam_id', $exam->id)->count())->toBe(540);
});

it('logs every change with who made it and the value it replaced', function () {
    putScores($this->teacherUser, cells($this->math->id, ['10']));
    putScores($this->teacherUser, cells($this->math->id, ['10'])); // unchanged: no log
    putScores($this->office, cells($this->math->id, ['12']));

    $log = Activity::where('description', 'score.entered')->orderBy('id')->get();
    expect($log)->toHaveCount(2)
        ->and($log[1]->causer_id)->toBe($this->office->id)
        ->and($log[1]->properties['old'])->toBe('10')
        ->and($log[1]->properties['new'])->toBe('12');
});

it('exports a subject sheet and imports it back, matching students by code', function () {
    $this->actingAs($this->teacherUser)
        ->get("/exams/{$this->exam->id}/scores/export?classroom={$this->sevenA->id}&subject={$this->math->id}")
        ->assertOk()->assertDownload('scores-7A-MATH.xlsx');

    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray([
        ['No', 'Code', 'Name', 'Latin', 'MATH'],
        [1, $this->enrollments[0]->student->student_code, '', '', 45],
        [2, $this->enrollments[1]->student->student_code, '', '', 'A'],
        [3, 'NOBODY', '', '', 20],
    ], null, 'A1', true);
    $path = tempnam(sys_get_temp_dir(), 'sala').'.xlsx';
    (new Xlsx($book))->save($path);

    $this->actingAs($this->teacherUser)->post("/exams/{$this->exam->id}/scores/import", [
        'file' => new UploadedFile($path, 'math.xlsx', null, null, true),
        'classroom_id' => $this->sevenA->id, 'subject_id' => $this->math->id,
    ])->assertSessionHas('score_import_errors', fn ($errors) => count($errors) === 1 && $errors[0]['line'] === 4);

    expect(Score::where('subject_id', $this->math->id)->count())->toBe(2);
});

it('lets a chosen approver who does not teach approve, and nobody else submit', function () {
    putScores($this->teacherUser, cells($this->math->id, ['10', '20', '30']));
    $sheet = ExamSubjectStatus::where('exam_id', $this->exam->id)->where('classroom_id', $this->sevenA->id)->where('subject_id', $this->math->id)->firstOrFail();

    $guardian = member($this->school, Role::Guardian);
    $this->actingAs($guardian)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'submitted'])->assertForbidden();
    $this->actingAs($guardian)->get('/exams')->assertForbidden();
    $this->actingAs($guardian)->get("/exams/{$this->exam->id}")->assertForbidden();

    $this->actingAs($this->teacherUser)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'submitted']);

    $head = member($this->school, Role::Registrar); // not a teacher, no exam permission
    ApprovalAssignment::create([
        'academic_year_id' => $this->sevenA->academic_year_id, 'grade_level_id' => $this->sevenA->grade_level_id,
        'approver_type' => ApproverType::User, 'user_id' => $head->id,
    ]);
    $this->actingAs($head)->get("/exams/{$this->exam->id}")->assertOk();
    $this->actingAs($head)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'approved'])
        ->assertSessionHasNoErrors();
    expect($sheet->fresh()->status)->toBe(SubjectExamStatus::Approved);
});

it('404s on another exam\'s subject sheet and on a class outside the exam', function () {
    $other = openExam([$this->sevenA->id]);
    $sheet = ExamSubjectStatus::where('exam_id', $other->id)->firstOrFail();

    $this->actingAs($this->office)->post("/exams/{$this->exam->id}/subjects/{$sheet->id}/transition", ['status' => 'submitted'])->assertNotFound();

    $outside = classesFor(makeSchool(), ['9A'])['9A'];
    $this->actingAs($this->office)->withSession(['school_id' => $this->school->id])
        ->get("/exams/{$this->exam->id}/scores?classroom={$outside->id}")->assertNotFound();
});
