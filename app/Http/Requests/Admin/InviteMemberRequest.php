<?php

namespace App\Http\Requests\Admin;

use App\Domain\School\Enums\Role;
use App\Http\Requests\InvitationRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends InvitationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(Role::class)],
            ...parent::rules(),
        ];
    }

    public function role(): Role
    {
        return Role::from((string) $this->validated('role'));
    }
}
