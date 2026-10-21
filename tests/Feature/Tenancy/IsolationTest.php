<?php

use App\Domain\Academic\Models\AcademicYear;
use App\Domain\Academic\Models\GradeLevel;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\MoeysTemplate;
use App\Domain\School\Actions\AddMember;
use App\Domain\School\Actions\InviteMember;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\SchoolInvitation;
use App\Support\Tenancy\CurrentSchool;

// Accepted when: two schools cannot see each other's data. Every school route must 404 for outsiders.

beforeEach(function () {
    $this->schoolA = makeSchool(['code' => 'school-a']);
    $this->schoolB = makeSchool(['code' => 'school-b']);
    $this->directorA = member($this->schoolA, Role::Director);
    $this->directorB = member($this->schoolB, Role::Director);
});

it('refuses to switch into a school the user does not belong to', function () {
    $this->actingAs($this->directorA)
        ->post('/schools/switch', ['school_id' => $this->schoolB->id])
        ->assertNotFound();
});

it('ignores a forged session school and falls back to the user\'s own', function () {
    $this->actingAs($this->directorA)
        ->withSession(['school_id' => $this->schoolB->id])
        ->get('/members')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('school.id', $this->schoolA->id)
            ->where('members', fn ($members) => collect($members)->pluck('id')->doesntContain($this->directorB->id)));
});

it('returns 404 on every school route reached through another school\'s subdomain', function (string $method, string $uri) {
    config(['app.tenant_domain' => 'sala.test']);

    $this->actingAs($this->directorA)
        ->call($method, "http://school-b.sala.test{$uri}")
        ->assertNotFound();
})->with([
    ['GET', '/'],
    ['GET', '/members'],
    ['GET', '/settings'],
    ['POST', '/settings'],
    ['POST', '/members/invitations'],
    ['GET', '/security'],
]);

it('serves the user\'s own school on its subdomain', function () {
    config(['app.tenant_domain' => 'sala.test']);

    $this->actingAs($this->directorA)->get('http://school-a.sala.test/members')->assertOk();
});

it('cannot reset the password of a member of another school', function () {
    $this->actingAs($this->directorA)
        ->post("/members/{$this->directorB->id}/password")
        ->assertNotFound();

    expect(Hash::check('password', $this->directorB->fresh()->password))->toBeTrue();
});

it('scopes school-owned models to the current school and fills school_id', function () {
    app(InviteMember::class)->handle($this->schoolA, Role::SubjectTeacher, 'a@sala.test', null, null);
    app(InviteMember::class)->handle($this->schoolB, Role::SubjectTeacher, 'b@sala.test', null, null);

    $current = app(CurrentSchool::class);

    $current->set($this->schoolA);
    expect(SchoolInvitation::pluck('email')->all())->toBe(['a@sala.test']);

    $current->set($this->schoolB);
    expect(SchoolInvitation::pluck('email')->all())->toBe(['b@sala.test']);

    $created = SchoolInvitation::create([
        'role' => Role::Guardian, 'email' => 'c@sala.test', 'token_hash' => str_repeat('c', 64), 'expires_at' => now()->addDay(),
    ]);
    expect($created->school_id)->toBe($this->schoolB->id);
});

it('keeps role assignments per school', function () {
    $teacher = member($this->schoolA, Role::SubjectTeacher);
    app(AddMember::class)->handle($this->schoolB, $teacher, Role::Guardian);

    $this->actingAs($teacher)->withSession(['school_id' => $this->schoolA->id])->get('/')
        ->assertInertia(fn ($page) => $page->where('roles', ['subject_teacher']));
    $this->actingAs($teacher->fresh())->withSession(['school_id' => $this->schoolB->id])->get('/')
        ->assertInertia(fn ($page) => $page->where('roles', ['guardian']));
});

it('404s when a route binds another school\'s record', function (string $method, string $uri) {
    $officeA = member($this->schoolA, Role::AcademicOffice);
    $subject = app(CurrentSchool::class)->runAs($this->schoolB, fn () => Subject::create([
        'code' => 'MATH', 'name_km' => 'គណិតវិទ្យា', 'name_en' => 'Mathematics', 'category' => 'science',
    ]));

    $this->actingAs($officeA)
        ->call($method, str_replace('{id}', (string) $subject->id, $uri), [
            'code' => 'HACK', 'name_km' => 'x', 'name_en' => 'x', 'category' => 'other',
        ])
        ->assertNotFound();

    expect($subject->fresh()->code)->toBe('MATH');
})->with([
    ['PUT', '/academic/subjects/{id}'],
    ['DELETE', '/academic/subjects/{id}'],
]);

it('refuses another school\'s grade when creating a class', function () {
    $officeA = member($this->schoolA, Role::AcademicOffice);
    $current = app(CurrentSchool::class);
    $yearA = $current->runAs($this->schoolA, fn () => AcademicYear::create([
        'name' => '2026–2027', 'start_date' => '2026-11-01', 'end_date' => '2027-08-31',
    ]));
    $gradeB = $current->runAs($this->schoolB, fn () => GradeLevel::create(
        MoeysTemplate::grade(7)
    ));

    $this->actingAs($officeA)->post('/academic/classrooms', [
        'academic_year_id' => $yearA->id, 'grade_level_id' => $gradeB->id, 'name' => '7A', 'shift' => 'morning',
    ])->assertSessionHasErrors('grade_level_id');
});
