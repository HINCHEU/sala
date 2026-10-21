<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\SchoolRequest;

class TrackRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', $this->uniqueInSchool('tracks', 'code', $this->route('track'))],
            'name_km' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
        ];
    }
}
