<?php

use App\Domain\Academic\Models\Subject;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Enums\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;

beforeEach(function () {
    $this->school = makeSchool();
    $this->director = member($this->school, Role::Director);
    $this->classes = classesFor($this->school);
});

$teacher = fn (array $overrides = []) => [
    'name_km' => 'ហេង សុភាព', 'name_en' => 'Heng Sopheap', 'gender' => 'male', 'phone' => '097 555 1234',
    'employment_type' => 'civil_servant', 'status' => 'active', ...$overrides,
];

it('saves a teacher with the subjects they can teach and a normalised phone', function () use ($teacher) {
    $subjects = Subject::whereIn('code', ['MATH', 'PHY'])->pluck('id')->all();

    $this->actingAs($this->director)->post('/people/teachers', $teacher(['subject_ids' => $subjects, 'staff_code' => 'T-01']))
        ->assertSessionHasNoErrors();

    $saved = Teacher::with('subjects')->firstOrFail();
    expect($saved->phone)->toBe('+855975551234')
        ->and($saved->subjects->pluck('code')->sort()->values()->all())->toBe(['MATH', 'PHY']);

    $this->actingAs($this->director)->post('/people/teachers', $teacher(['staff_code' => 'T-01']))->assertSessionHasErrors('staff_code');
});

it('refuses another school\'s subject', function () use ($teacher) {
    $other = makeSchool();
    $foreign = app(CurrentSchool::class)->runAs($other, fn () => Subject::create([
        'code' => 'ART', 'name_km' => 'សិល្បៈ', 'name_en' => 'Art', 'category' => 'other',
    ]));

    $this->actingAs($this->director)->post('/people/teachers', $teacher(['subject_ids' => [$foreign->id]]))
        ->assertSessionHasErrors('subject_ids.0');
});

it('gives a teacher a sign-in link that links the new account to the teacher', function () use ($teacher) {
    $this->actingAs($this->director)->post('/people/teachers', $teacher());
    $record = Teacher::firstOrFail();

    $this->actingAs($this->director)->post("/people/teachers/{$record->id}/invite")->assertSessionHas('invite_link');
    $link = session('invite_link');
    auth()->logout();

    $this->post($link, ['name' => 'ហេង សុភាព', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
        ->assertRedirect('/');

    $user = User::where('phone', '+855975551234')->firstOrFail();
    expect($record->fresh()->user_id)->toBe($user->id);

    $this->actingAs($this->director)->post("/people/teachers/{$record->id}/invite")->assertSessionHas('error', __('people.has_login'));
});

it('needs an email or phone before it can invite', function () use ($teacher) {
    $this->actingAs($this->director)->post('/people/teachers', $teacher(['phone' => '']));

    $this->actingAs($this->director)->post('/people/teachers/'.Teacher::value('id').'/invite')
        ->assertSessionHas('error', __('people.needs_contact'));
});

it('sets a class\'s homeroom teacher, from this school only', function () use ($teacher) {
    $this->actingAs($this->director)->post('/people/teachers', $teacher());
    $class = $this->classes['7A'];
    $payload = fn (int $teacherId) => [
        'academic_year_id' => $class->academic_year_id, 'grade_level_id' => $class->grade_level_id,
        'name' => '7A', 'shift' => 'morning', 'homeroom_teacher_id' => $teacherId,
    ];

    $this->actingAs($this->director)->put("/academic/classrooms/{$class->id}", $payload(Teacher::value('id')))->assertSessionHasNoErrors();
    expect($class->fresh()->homeroomTeacher->name_km)->toBe('ហេង សុភាព');

    $other = makeSchool();
    $foreign = app(CurrentSchool::class)->runAs($other, fn () => Teacher::create($teacher(['phone' => null])));
    $this->actingAs($this->director)->put("/academic/classrooms/{$class->id}", $payload($foreign->id))->assertSessionHasErrors('homeroom_teacher_id');
});
