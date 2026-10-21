<?php

namespace App\Http\Requests\Platform;

use App\Domain\School\Enums\SchoolStage;
use App\Domain\School\Enums\SchoolType;
use App\Http\Requests\InvitationRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends InvitationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // The code becomes the subdomain, so lowercase letters, digits and dashes only.
            'code' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9][a-z0-9-]*$/', Rule::unique('schools', 'code')],
            'name_km' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(SchoolType::class)],
            'stage' => ['required', Rule::enum(SchoolStage::class)],
            'province' => ['required', 'string', 'max:100'],
            ...parent::rules(),
        ];
    }

    /** @return array<string, mixed> */
    public function schoolData(): array
    {
        return $this->safe()->only(['code', 'name_km', 'name_en', 'type', 'stage', 'province']);
    }
}
