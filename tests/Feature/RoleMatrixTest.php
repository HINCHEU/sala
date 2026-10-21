<?php

use App\Domain\School\Enums\Role;
use App\Models\User;

// Who may open which page. Add a row per page as milestones add them.

it('lets only the director manage settings and members', function (Role $role, int $status) {
    $school = makeSchool();
    $user = member($school, $role);

    $this->actingAs($user)->get('/settings')->assertStatus($status);
    $this->actingAs($user)->get('/members')->assertStatus($status);
})->with([
    [Role::Director, 200],
    [Role::AcademicOffice, 403],
    [Role::Registrar, 403],
    [Role::Accountant, 403],
    [Role::HomeroomTeacher, 403],
    [Role::SubjectTeacher, 403],
    [Role::Guardian, 403],
    [Role::Student, 403],
]);

it('keeps platform pages for platform admins', function () {
    $school = makeSchool();

    $this->actingAs(member($school, Role::Director))->get('/platform/schools')->assertForbidden();
    $this->actingAs(User::factory()->create(['is_platform_admin' => true]))->get('/platform/schools')->assertOk();
});

it('gives platform admins support access to any school', function () {
    $school = makeSchool();
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($admin)->withSession(['school_id' => $school->id])->get('/settings')->assertOk();
});

it('opens a page for a platform admin with no school chosen yet', function () {
    makeSchool();
    makeSchool();

    // The fallback school comes from a multi-row query; the shared props must not lazy-load its logo.
    $this->actingAs(User::factory()->create(['is_platform_admin' => true]))->get('/platform/schools')->assertOk();
});
