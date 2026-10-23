<?php

use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Subject;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Enums\Role;
use App\Domain\Teaching\Enums\ApproverType;
use App\Domain\Teaching\Models\ApprovalAssignment;
use App\Domain\Teaching\Models\TeachingAssignment;

beforeEach(function () {
    $this->school = makeSchool();
    $this->director = member($this->school, Role::Director);
    $this->academicOffice = member($this->school, Role::AcademicOffice);
    $this->classes = classesFor($this->school);
    $this->classroom = $this->classes['7A'];

    $this->math = Subject::where('code', 'MATH')->firstOrFail();
    $this->khmer = Subject::where('code', 'KHM')->firstOrFail();

    $this->teacher = Teacher::create([
        'school_id' => $this->school->id,
        'name_km' => 'ហេង សុភាព',
        'name_en' => 'Heng Sopheap',
        'gender' => 'male',
        'phone' => '+855975551234',
        'employment_type' => 'civil_servant',
        'status' => 'active',
    ]);
});

it('lets director and academic office access teaching index, but blocks students and guardians', function () {
    $this->actingAs($this->director)->get('/academic/teaching')->assertOk();
    $this->actingAs($this->academicOffice)->get('/academic/teaching')->assertOk();

    $student = member($this->school, Role::Student);
    $this->actingAs($student)->get('/academic/teaching')->assertForbidden();
});

it('assigns teachers and weekly hours to curriculum subjects for a classroom', function () {
    $payload = [
        'assignments' => [
            [
                'subject_id' => $this->math->id,
                'teacher_id' => $this->teacher->id,
                'hours_per_week' => 6,
            ],
            [
                'subject_id' => $this->khmer->id,
                'teacher_id' => $this->teacher->id,
                'hours_per_week' => 5,
            ],
        ],
    ];

    $this->actingAs($this->academicOffice)
        ->put("/academic/teaching/classrooms/{$this->classroom->id}", $payload)
        ->assertSessionHas('success');

    expect(TeachingAssignment::where('classroom_id', $this->classroom->id)->count())->toBe(2);

    $mathAssignment = TeachingAssignment::where('classroom_id', $this->classroom->id)
        ->where('subject_id', $this->math->id)
        ->firstOrFail();

    expect($mathAssignment->teacher_id)->toBe($this->teacher->id)
        ->and($mathAssignment->hours_per_week)->toBe(6);
});

it('clears teaching assignment when teacher_id is set to null', function () {
    TeachingAssignment::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->classroom->academic_year_id,
        'classroom_id' => $this->classroom->id,
        'subject_id' => $this->math->id,
        'teacher_id' => $this->teacher->id,
        'hours_per_week' => 4,
    ]);

    expect(TeachingAssignment::where('classroom_id', $this->classroom->id)->count())->toBe(1);

    $payload = [
        'assignments' => [
            [
                'subject_id' => $this->math->id,
                'teacher_id' => null,
                'hours_per_week' => 0,
            ],
        ],
    ];

    $this->actingAs($this->academicOffice)
        ->put("/academic/teaching/classrooms/{$this->classroom->id}", $payload)
        ->assertSessionHas('success');

    expect(TeachingAssignment::where('classroom_id', $this->classroom->id)->count())->toBe(0);
});

it('copies teaching assignments from a prior year matching classroom name and grade', function () {
    $sourceYear = AcademicYear::where('is_current', true)->firstOrFail();

    // Create target year
    $targetYear = app(CreateAcademicYear::class)->handle([
        'name' => '2027–2028',
        'start_date' => '2027-11-01',
        'end_date' => '2028-08-31',
    ]);

    // Create matching classroom in target year
    $targetClassroom = Classroom::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $targetYear->id,
        'grade_level_id' => $this->classroom->grade_level_id,
        'name' => '7A',
        'shift' => 'morning',
    ]);

    // Create an assignment in source year
    TeachingAssignment::create([
        'school_id' => $this->school->id,
        'academic_year_id' => $sourceYear->id,
        'classroom_id' => $this->classroom->id,
        'subject_id' => $this->math->id,
        'teacher_id' => $this->teacher->id,
        'hours_per_week' => 5,
    ]);

    $payload = [
        'source_year_id' => $sourceYear->id,
        'target_year_id' => $targetYear->id,
    ];

    $this->actingAs($this->academicOffice)
        ->post('/academic/teaching/copy', $payload)
        ->assertSessionHas('success');

    $copied = TeachingAssignment::where('classroom_id', $targetClassroom->id)->first();
    expect($copied)->not->toBeNull()
        ->and($copied->subject_id)->toBe($this->math->id)
        ->and($copied->teacher_id)->toBe($this->teacher->id)
        ->and($copied->hours_per_week)->toBe(5);
});

it('saves grade-level approval assignments', function () {
    $year = AcademicYear::where('is_current', true)->firstOrFail();
    $grade = GradeLevel::where('number', 7)->firstOrFail();

    $payload = [
        'year_id' => $year->id,
        'approvals' => [
            [
                'grade_level_id' => $grade->id,
                'approver_type' => 'role',
                'role' => Role::AcademicOffice->value,
                'user_id' => null,
            ],
        ],
    ];

    $this->actingAs($this->academicOffice)
        ->put('/academic/teaching/approvals', $payload)
        ->assertSessionHas('success');

    $approval = ApprovalAssignment::where('academic_year_id', $year->id)
        ->where('grade_level_id', $grade->id)
        ->firstOrFail();

    expect($approval->approver_type)->toBe(ApproverType::Role)
        ->and($approval->role)->toBe(Role::AcademicOffice->value);
});

it('synchronizes HomeroomTeacher Spatie role when homeroom teacher is updated', function () {
    $teacherUser = member($this->school, Role::SubjectTeacher);
    $this->teacher->forceFill(['user_id' => $teacherUser->id])->save();

    expect($teacherUser->hasRole(Role::HomeroomTeacher->value))->toBeFalse();

    // Assign as homeroom teacher to 7A
    $this->actingAs($this->director)
        ->put("/academic/classrooms/{$this->classroom->id}", [
            'academic_year_id' => $this->classroom->academic_year_id,
            'name' => '7A',
            'grade_level_id' => $this->classroom->grade_level_id,
            'shift' => 'morning',
            'homeroom_teacher_id' => $this->teacher->id,
        ])
        ->assertSessionHasNoErrors();

    $teacherUser->refresh();
    expect($teacherUser->hasRole(Role::HomeroomTeacher->value))->toBeTrue();

    // Remove as homeroom teacher
    $this->actingAs($this->director)
        ->put("/academic/classrooms/{$this->classroom->id}", [
            'academic_year_id' => $this->classroom->academic_year_id,
            'name' => '7A',
            'grade_level_id' => $this->classroom->grade_level_id,
            'shift' => 'morning',
            'homeroom_teacher_id' => null,
        ])
        ->assertSessionHasNoErrors();

    $teacherUser->refresh();
    expect($teacherUser->hasRole(Role::HomeroomTeacher->value))->toBeFalse();
});
