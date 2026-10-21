<?php

namespace App\Http\Requests\Academic;

use App\Domain\Academic\Enums\GradeStage;
use App\Http\Requests\SchoolRequest;
use Illuminate\Validation\Rule;

class GradeLevelRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:0', 'max:12', $this->uniqueInSchool('grade_levels', 'number', $this->route('grade'))],
            'name_km' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'stage' => ['required', Rule::enum(GradeStage::class)],
            'has_tracks' => ['boolean'],
        ];
    }
}
