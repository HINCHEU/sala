<?php

namespace App\Domain\School\Actions;

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

            $invitation->forceFill(['accepted_at' => now(), 'accepted_by' => $user->id])->save();
        });
    }
}
