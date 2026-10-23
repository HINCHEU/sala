<?php

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\CurriculumSubject;
use App\Domain\Academic\Models\Subject;
use App\Domain\Exams\Enums\ExamStatus;
use App\Domain\Exams\Enums\ExamType;
use App\Domain\Exams\Enums\SubjectExamStatus;
use App\Domain\Exams\Models\Exam;
use App\Domain\Exams\Models\ExamSubjectStatus;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Enums\Role;
use App\Domain\Teaching\Models\TeachingAssignment;

beforeEach(function () {
    $this->school = makeSchool();
    $this->director = member($this->school, Role::Director);
    $this->academicOffice = member($this->school, Role::AcademicOffice);
    $this->classes = classesFor($this->school);
    $this->classroom = $this->classes['7A'];
    $this->year = AcademicYear::where('is_current', true)->firstOrFail();

    $this->math = Subject::where('code', 'MATH')->firstOrFail();

    $this->teacherUser = member($this->school, Role::SubjectTeacher);
    $this->teacher = Teacher::create([
        'school_id' => $this->school->id,
        'user_id' => $this->teacherUser->id,
        'name_km' => 'ហេង សុភាព',
        'name_en' => 'Heng Sopheap',
        'gender' => 'male',
        'phone' => '+855975551234',
        'employment_type' => 'civil_servant',
        'status' => 'active',
    ]);

    TeachingAssignment::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'classroom_id' => $this->classroom->id,
        'subject_id' => $this->math->id,
        'teacher_id' => $this->teacher->id,
        'hours_per_week' => 6,
    ]);

    CurriculumSubject::create([
        'academic_year_id' => $this->year->id,
        'grade_level_id' => $this->classroom->grade_level_id,
        'subject_id' => $this->math->id,
        'max_score' => 100,
        'pass_score' => 50,
        'coefficient' => 2,
        'sort' => 1,
    ]);
});

it('allows director and academic office to create exams, but blocks students', function () {
    $payload = [
        'academic_year_id' => $this->year->id,
        'name' => 'Monthly Exam November',
        'type' => 'monthly',
        'month' => 11,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'classroom_ids' => [$this->classroom->id],
    ];

    $this->actingAs($this->academicOffice)->post('/exams', $payload)
        ->assertRedirect();

    $exam = Exam::where('name', 'Monthly Exam November')->first();
    expect($exam)->not->toBeNull()
        ->and($exam->status)->toBe(ExamStatus::Draft)
        ->and($exam->classrooms()->count())->toBe(1);

    $student = member($this->school, Role::Student);
    $this->actingAs($student)->post('/exams', $payload)
        ->assertForbidden();
});

it('transitions exam to open and initializes ExamSubjectStatus records for curriculum subjects', function () {
    $exam = Exam::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'name' => 'Draft Exam',
        'type' => ExamType::Monthly,
        'month' => 11,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'status' => ExamStatus::Draft,
    ]);
    $exam->syncClassrooms([$this->classroom->id]);

    expect(ExamSubjectStatus::where('exam_id', $exam->id)->count())->toBe(0);

    // Open the exam
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/transition", ['status' => 'open'])
        ->assertSessionHas('success');

    $exam->refresh();
    expect($exam->status)->toBe(ExamStatus::Open);

    // Status records should be initialized
    $statuses = ExamSubjectStatus::where('exam_id', $exam->id)->get();
    expect($statuses->count())->toBeGreaterThanOrEqual(1)
        ->and($statuses->first()->status)->toBe(SubjectExamStatus::Draft);
});

it('strictly adheres to the lifecycle state machine order', function () {
    $exam = Exam::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'name' => 'Lifecycle Test Exam',
        'type' => ExamType::Semester,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'status' => ExamStatus::Draft,
    ]);
    $exam->syncClassrooms([$this->classroom->id]);

    // draft -> published is NOT allowed directly
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/transition", ['status' => 'published'])
        ->assertSessionHasErrors('status');

    // draft -> open (OK)
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/transition", ['status' => 'open'])
        ->assertSessionHasNoErrors();

    // open -> approved (OK)
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/transition", ['status' => 'approved'])
        ->assertSessionHasNoErrors();

    // approved -> locked (OK)
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/transition", ['status' => 'locked'])
        ->assertSessionHasNoErrors();

    // locked -> published (OK)
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/transition", ['status' => 'published'])
        ->assertSessionHasNoErrors();

    $exam->refresh();
    expect($exam->status)->toBe(ExamStatus::Published);
});

it('allows assigned teacher to submit subject score', function () {
    $exam = Exam::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'name' => 'Open Exam',
        'type' => ExamType::Monthly,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'status' => ExamStatus::Open,
    ]);
    $exam->syncClassrooms([$this->classroom->id]);

    $statusRecord = ExamSubjectStatus::create([
        'school_id' => $this->school->id,
        'exam_id' => $exam->id,
        'classroom_id' => $this->classroom->id,
        'subject_id' => $this->math->id,
        'status' => SubjectExamStatus::Draft,
    ]);

    // Teacher submits score
    $this->actingAs($this->teacherUser)
        ->post("/exams/{$exam->id}/subjects/{$statusRecord->id}/transition", [
            'status' => 'submitted',
        ])
        ->assertSessionHas('success');

    $statusRecord->refresh();
    expect($statusRecord->status)->toBe(SubjectExamStatus::Submitted)
        ->and($statusRecord->submitted_by)->toBe($this->teacherUser->id);
});

it('allows approver to approve or return subject scores with a required reason', function () {
    $exam = Exam::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'name' => 'Approval Exam',
        'type' => ExamType::Monthly,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'status' => ExamStatus::Open,
    ]);
    $exam->syncClassrooms([$this->classroom->id]);

    $statusRecord = ExamSubjectStatus::create([
        'school_id' => $this->school->id,
        'exam_id' => $exam->id,
        'classroom_id' => $this->classroom->id,
        'subject_id' => $this->math->id,
        'status' => SubjectExamStatus::Submitted,
        'submitted_by' => $this->teacherUser->id,
        'submitted_at' => now(),
    ]);

    // Returning without reason fails
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/subjects/{$statusRecord->id}/transition", [
            'status' => 'returned',
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');

    // Returning with reason succeeds
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/subjects/{$statusRecord->id}/transition", [
            'status' => 'returned',
            'reason' => 'Missing student 5 score',
        ])
        ->assertSessionHasNoErrors();

    $statusRecord->refresh();
    expect($statusRecord->status)->toBe(SubjectExamStatus::Returned)
        ->and($statusRecord->returned_reason)->toBe('Missing student 5 score');

    // Teacher re-submits
    $this->actingAs($this->teacherUser)
        ->post("/exams/{$exam->id}/subjects/{$statusRecord->id}/transition", [
            'status' => 'submitted',
        ])
        ->assertSessionHasNoErrors();

    // Approver approves
    $this->actingAs($this->academicOffice)
        ->post("/exams/{$exam->id}/subjects/{$statusRecord->id}/transition", [
            'status' => 'approved',
        ])
        ->assertSessionHasNoErrors();

    $statusRecord->refresh();
    expect($statusRecord->status)->toBe(SubjectExamStatus::Approved)
        ->and($statusRecord->approved_by)->toBe($this->academicOffice->id);
});

it('only allows draft exams to be deleted and prevents editing locked exams', function () {
    $exam = Exam::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->year->id,
        'name' => 'Delete Test Exam',
        'type' => ExamType::Monthly,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'status' => ExamStatus::Open,
    ]);

    // Deleting an open exam fails (403)
    $this->actingAs($this->academicOffice)->delete("/exams/{$exam->id}")
        ->assertForbidden();

    // Locking the exam
    $exam->update(['status' => ExamStatus::Locked]);

    // Editing a locked exam fails
    $this->actingAs($this->academicOffice)->get("/exams/{$exam->id}/edit")
        ->assertForbidden();

    $this->actingAs($this->academicOffice)->put("/exams/{$exam->id}", [
        'name' => 'Modified Name',
        'academic_year_id' => $this->year->id,
        'type' => 'monthly',
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'classroom_ids' => [$this->classroom->id],
    ])->assertSessionHasErrors('status');
});
