<?php

namespace App\Domain\School\Actions;

use App\Domain\People\Models\Teacher;
use App\Domain\School\Models\SchoolInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptInvitation
{
    public function __construct(private AddMember $addMember) {}

    public function handle(SchoolInvitation $invitation, User $user): void
    {
        DB::transaction(function () use ($invitation, $user) {
            $this->addMember->handle($invitation->school, $user, $invitation->role);

            if ($invitation->teacher_id) {
                // The invitee is a guest here, so there is no current school to scope by: use the invitation's.
                Teacher::withoutGlobalScope('school')
                    ->where('school_id', $invitation->school_id)
                    ->whereKey($invitation->teacher_id)
                    ->update(['user_id' => $user->id]);
            }

            $invitation->forceFill(['accepted_at' => now(), 'accepted_by' => $user->id])->save();
        });
    }
}
