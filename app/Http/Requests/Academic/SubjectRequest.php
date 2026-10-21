<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Enums\SubjectCategory;
use App\Http\Requests\SchoolRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', $this->uniqueInSchool('subjects', 'code', $this->route('subject'))],
            'name_km' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::enum(SubjectCategory::class)],
        ];
    }
}
