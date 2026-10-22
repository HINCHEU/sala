<?php

use App\Domain\Academic\Actions\ApplyMoeysTemplate;
use App\Domain\Academic\Actions\CreateAcademicYear;
use App\Domain\Academic\Models\Classroom;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\People\Models\Student;
use App\Domain\School\Actions\AddMember;
use App\Domain\School\Actions\CreateSchool;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/** A school with all its roles, created the way the platform does it. */
function makeSchool(array $attributes = []): School
{
    return app(CreateSchool::class)->handle(School::factory()->raw($attributes));
}

/**
 * Makes $school current and gives it the MoEYS grades, a current year and classes of grade 7 named $names.
 *
 * @return array<string, Classroom> keyed by class name
 */
function classesFor(School $school, array $names = ['7A', '7B']): array
{
    app(CurrentSchool::class)->set($school);
    app(ApplyMoeysTemplate::class)->handle();
    $year = app(CreateAcademicYear::class)->handle(['name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
    $grade = GradeLevel::where('number', 7)->value('id');

    return collect($names)->mapWithKeys(fn (string $name) => [$name => Classroom::create([
        'academic_year_id' => $year->id, 'grade_level_id' => $grade, 'name' => $name, 'shift' => 'morning',
    ])])->all();
}

/** A student in the current school. */
function makeStudent(string $last = 'សុខ', string $first = 'ដារ៉ា', array $attributes = []): Student
{
    static $code = 0;

    return Student::create([
        'student_code' => 'T'.++$code, 'last_name_km' => $last, 'first_name_km' => $first,
        'gender' => 'female', 'status' => 'active', ...$attributes,
    ]);
}

/** A user holding $role in $school. Roles that require 2FA get it confirmed unless $twoFactor is false. */
function member(School $school, Role $role, bool $twoFactor = true, array $attributes = []): User
{
    $user = User::factory()->create([
        'two_factor_confirmed_at' => $twoFactor && $role->requiresTwoFactor() ? now() : null,
        ...$attributes,
    ]);
    app(AddMember::class)->handle($school, $user, $role);

    return $user;
}
