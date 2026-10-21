<?php

namespace App\Domain\School\Actions;

use App\Domain\School\Enums\MembershipStatus;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class AddMember
{
    public function __construct(private CurrentSchool $currentSchool) {}

    public function handle(School $school, User $user, Role $role): void
    {
        DB::transaction(function () use ($school, $user, $role) {
            $school->users()->syncWithoutDetaching([$user->id => ['status' => MembershipStatus::Active->value]]);

            // Roles are team-scoped: assign while the school is the current team.
            $this->currentSchool->runAs($school, function () use ($user, $role) {
                $user->unsetRelation('roles')->assignRole($role->value);
            });
            $user->unsetRelation('roles')->unsetRelation('permissions');

            activity()->performedOn($school)->causedBy(auth()->user())
                ->withProperties(['user_id' => $user->id, 'role' => $role->value])
                ->log('member.added');
        });
    }
}
