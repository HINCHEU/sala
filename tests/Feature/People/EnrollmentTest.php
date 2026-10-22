<?php

use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\People\Models\Enrollment;
use App\Domain\School\Enums\Role;

beforeEach(function () {
    $this->school = makeSchool();
    $this->registrar = member($this->school, Role::Registrar);
    $this->classes = classesFor($this->school);
});

/** Names in the class's roll-number order. */
function rollCall(Classroom $classroom): array
{
    return Enrollment::where('classroom_id', $classroom->id)->orderBy('roll_no')->with('student')->get()
        ->map(fn (Enrollment $e) => "{$e->roll_no} {$e->student->nameKm()}")->all();
}

it('enrolls students and numbers the class in Khmer alphabetical order', function () {
    $ids = [makeStudent('សុខ', 'ដារ៉ា')->id, makeStudent('កែវ', 'មុនី')->id, makeStudent('ចាន់', 'សុភា')->id];

    $this->actingAs($this->registrar)->post('/people/enrollments', ['classroom_id' => $this->classes['7A']->id, 'student_ids' => $ids])
        ->assertSessionHas('success');

    expect(rollCall($this->classes['7A']))->toBe(['1 កែវ មុនី', '2 ចាន់ សុភា', '3 សុខ ដារ៉ា']);
});

it('allows one class per student per year', function () {
    $student = makeStudent();
    $this->actingAs($this->registrar)->post('/people/enrollments', ['classroom_id' => $this->classes['7A']->id, 'student_ids' => [$student->id]]);

    $this->actingAs($this->registrar)->post('/people/enrollments', ['classroom_id' => $this->classes['7B']->id, 'student_ids' => [$student->id]])
        ->assertSessionHasErrors('student_ids');

    $next = app(CreateAcademicYear::class)->handle(['name' => '2027–2028', 'start_date' => '2027-11-01', 'end_date' => '2028-08-31']);
    $eightA = Classroom::create(['academic_year_id' => $next->id, 'grade_level_id' => GradeLevel::where('number', 8)->value('id'), 'name' => '8A', 'shift' => 'morning']);
    $this->actingAs($this->registrar)->post('/people/enrollments', ['classroom_id' => $eightA->id, 'student_ids' => [$student->id]])
        ->assertSessionHasNoErrors();

    expect(Enrollment::where('student_id', $student->id)->count())->toBe(2);
});

it('moves students between classes of one year and renumbers both', function () {
    $a = makeStudent('ក', 'ក');
    $b = makeStudent('ខ', 'ខ');
    $c = makeStudent('គ', 'គ');
    $this->actingAs($this->registrar)->post('/people/enrollments', ['classroom_id' => $this->classes['7A']->id, 'student_ids' => [$a->id, $b->id, $c->id]]);
    $moving = Enrollment::where('student_id', $a->id)->firstOrFail();

    $this->actingAs($this->registrar)->put('/people/enrollments', ['classroom_id' => $this->classes['7B']->id, 'enrollment_ids' => [$moving->id]])
        ->assertSessionHas('success');

    expect(rollCall($this->classes['7A']))->toBe(['1 ខ ខ', '2 គ គ'])
        ->and(rollCall($this->classes['7B']))->toBe(['1 ក ក'])
        ->and($moving->fresh()->id)->toBe($moving->id); // the same enrollment row moved
});

it('refuses to move a student into another year\'s class', function () {
    $student = makeStudent();
    $this->actingAs($this->registrar)->post('/people/enrollments', ['classroom_id' => $this->classes['7A']->id, 'student_ids' => [$student->id]]);
    $next = app(CreateAcademicYear::class)->handle(['name' => '2027–2028', 'start_date' => '2027-11-01', 'end_date' => '2028-08-31']);
    $eightA = Classroom::create(['academic_year_id' => $next->id, 'grade_level_id' => GradeLevel::where('number', 8)->value('id'), 'name' => '8A', 'shift' => 'morning']);

    $this->actingAs($this->registrar)->put('/people/enrollments', [
        'classroom_id' => $eightA->id, 'enrollment_ids' => Enrollment::pluck('id')->all(),
    ])->assertSessionHasErrors('classroom_id');
});

it('removes students from a class and lists them as without a class again', function () {
    $a = makeStudent('ក', 'ក');
    $b = makeStudent('ខ', 'ខ');
    $this->actingAs($this->registrar)->post('/people/enrollments', ['classroom_id' => $this->classes['7A']->id, 'student_ids' => [$a->id, $b->id]]);

    $this->actingAs($this->registrar)->delete('/people/enrollments', ['enrollment_ids' => [Enrollment::where('student_id', $a->id)->value('id')]])
        ->assertSessionHas('success');

    expect(rollCall($this->classes['7A']))->toBe(['1 ខ ខ']);
    $this->actingAs($this->registrar)->get("/people/enrollment?classroom={$this->classes['7A']->id}")
        ->assertInertia(fn ($page) => $page
            ->where('candidates', fn ($c) => collect($c)->pluck('id')->all() === [$a->id])
            ->where('enrolled', fn ($e) => collect($e)->pluck('student_id')->all() === [$b->id]));
});
