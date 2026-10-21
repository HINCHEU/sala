<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\SchoolRequest;

class TermRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_km' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'sort' => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }
}
