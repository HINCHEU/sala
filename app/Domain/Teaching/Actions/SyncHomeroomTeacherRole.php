<?php

namespace App\Domain\Teaching\Actions;

use App\Domain\Academic\Models\Classroom;
use App\Domain\People\Models\Teacher;
use App\Domain\School\Enums\Role;
use App\Domain\School\Models\School;
use App\Support\Tenancy\CurrentSchool;

class SyncHomeroomTeacherRole
{
    public function __construct(private CurrentSchool $currentSchool) {}

    public function handle(School $school, ?int $teacherId): void
    {
        if (! $teacherId) {
            return;
        }

        $teacher = Teacher::withoutGlobalScope('school')
            ->where('school_id', $school->id)
            ->whereKey($teacherId)
            ->first();

        if (! $teacher || ! $teacher->user_id) {
            return;
        }

        $user = $teacher->user;
        if (! $user) {
            return;
        }

        $isHomeroom = Classroom::withoutGlobalScope('school')
            ->where('school_id', $school->id)
            ->where('homeroom_teacher_id', $teacher->id)
            ->exists();

        $this->currentSchool->runAs($school, function () use ($user, $isHomeroom) {
            $user->unsetRelation('roles');
            $hasRole = $user->hasRole(Role::HomeroomTeacher->value);

            if ($isHomeroom && ! $hasRole) {
                $user->assignRole(Role::HomeroomTeacher->value);
            } elseif (! $isHomeroom && $hasRole) {
                $user->removeRole(Role::HomeroomTeacher->value);
            }
        });

        $user->unsetRelation('roles')->unsetRelation('permissions');
    }
}
