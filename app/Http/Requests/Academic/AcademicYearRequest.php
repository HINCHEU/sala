<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\SchoolRequest;

class AcademicYearRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40', $this->uniqueInSchool('academic_years', 'name', $this->route('year'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }
}
