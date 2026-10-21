<?php

use App\Domain\School\Enums\Role;

it('sends roles that need 2FA to the security page until it is confirmed', function (Role $role) {
    $user = member(makeSchool(), $role, twoFactor: false);

    $this->actingAs($user)->get('/')->assertRedirect('/security');
    $this->actingAs($user)->get('/security')->assertOk()
        ->assertInertia(fn ($page) => $page->where('twoFactor.required', true));
})->with([Role::Director, Role::AcademicOffice, Role::Accountant]);

it('does not force 2FA on teachers', function () {
    $user = member(makeSchool(), Role::SubjectTeacher, twoFactor: false);

    $this->actingAs($user)->get('/')->assertOk();
});

it('shows a QR code once 2FA is enabled but not confirmed', function () {
    $user = member(makeSchool(), Role::Director, twoFactor: false);

    $this->actingAs($user)->post('/user/two-factor-authentication')->assertRedirect();
    $this->actingAs($user->fresh())->get('/security')
        ->assertInertia(fn ($page) => $page
            ->where('twoFactor.enabled', true)
            ->where('twoFactor.confirmed', false)
            ->where('twoFactor.qrSvg', fn ($svg) => str_contains($svg, '<svg')));
});
