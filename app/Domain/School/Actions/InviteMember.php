<?php

namespace App\Domain\School\Actions;

use App\Domain\School\Enums\Role;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolInvitation;
use App\Models\User;
use Illuminate\Support\Str;

class InviteMember
{
    public const VALID_DAYS = 7;

    /** Returns the invitation link; the plain token exists only in this link. */
    public function handle(School $school, Role $role, ?string $email, ?string $phone, ?User $inviter): string
    {
        $token = Str::random(48);

        SchoolInvitation::create([
            'school_id' => $school->id,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'token_hash' => hash('sha256', $token),
            'invited_by' => $inviter?->id,
            'expires_at' => now()->addDays(self::VALID_DAYS),
        ]);

        activity()->performedOn($school)->causedBy($inviter)
            ->withProperties(['email' => $email, 'phone' => $phone, 'role' => $role->value])
            ->log('member.invited');

        return route('invitations.show', $token);
    }
}
