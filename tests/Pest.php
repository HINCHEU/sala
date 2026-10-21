<?php

use App\Domain\School\Actions\AddMember;
use App\Domain\School\Actions\CreateSchool;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\School;
use App\Models\User;
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
