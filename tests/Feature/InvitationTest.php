<?php

use App\Domain\School\Enums\Role;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;

beforeEach(function () {
    $this->school = makeSchool();
    $this->director = member($this->school, Role::Director);
});

function inviteLink($test, array $data): string
{
    $test->actingAs($test->director)->post('/members/invitations', $data)->assertSessionHasNoErrors();

    return session('invite_link');
}

it('lets a new person create an account from a phone invitation', function () {
    $link = inviteLink($this, ['role' => 'subject_teacher', 'phone' => '012 345 678']);
    auth()->logout();

    $this->get($link)->assertInertia(fn ($page) => $page->where('state', 'register'));
    $this->post($link, ['name' => 'សុខ ដារ៉ា', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
        ->assertRedirect('/');

    $user = User::where('phone', '+85512345678')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    app(CurrentSchool::class)->set($this->school);
    expect($user->fresh()->hasRole('subject_teacher'))->toBeTrue();
});

it('works only once', function () {
    $link = inviteLink($this, ['role' => 'guardian', 'email' => 'parent@sala.test']);
    auth()->logout();

    $this->post($link, ['name' => 'Parent', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1']);
    auth()->logout();

    $this->get($link)->assertInertia(fn ($page) => $page->where('state', 'invalid'));
    $this->post($link, ['name' => 'Again', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
        ->assertNotFound();
});

it('asks an existing account to sign in, then accept', function () {
    $other = makeSchool();
    $teacher = member($other, Role::SubjectTeacher, attributes: ['email' => 'teacher@sala.test']);
    $link = inviteLink($this, ['role' => 'homeroom_teacher', 'email' => 'teacher@sala.test']);
    auth()->logout();

    $this->get($link)->assertInertia(fn ($page) => $page->where('state', 'login'));

    $this->actingAs($teacher)->post($link)->assertRedirect('/');
    expect($this->school->users()->whereKey($teacher->id)->exists())->toBeTrue();
});

it('refuses an invitation addressed to someone else', function () {
    $link = inviteLink($this, ['role' => 'accountant', 'email' => 'accountant@sala.test']);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get($link)->assertInertia(fn ($page) => $page->where('state', 'mismatch'));
    $this->actingAs($stranger)->post($link)->assertForbidden();
});

it('rejects an expired link', function () {
    $link = inviteLink($this, ['role' => 'registrar', 'email' => 'registrar@sala.test']);
    auth()->logout();

    $this->travel(8)->days();
    $this->get($link)->assertInertia(fn ($page) => $page->where('state', 'invalid'));
});

it('needs an email or a valid Cambodian phone', function () {
    $this->actingAs($this->director)->post('/members/invitations', ['role' => 'student'])
        ->assertSessionHasErrors(['email', 'phone']);
    $this->actingAs($this->director)->post('/members/invitations', ['role' => 'student', 'phone' => '123'])
        ->assertSessionHasErrors('phone');
});

it('lets the director set a temporary password for a member', function () {
    $teacher = member($this->school, Role::SubjectTeacher);

    $this->actingAs($this->director)->post("/members/{$teacher->id}/password")->assertSessionHas('temp_password');

    expect(Hash::check(session('temp_password')['password'], $teacher->fresh()->password))->toBeTrue();
});
