<?php

namespace App\Http\Requests\Academic;

use App\Http\Requests\SchoolRequest;

class RoomRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60', $this->uniqueInSchool('rooms', 'name', $this->route('room'))],
            'building' => ['nullable', 'string', 'max:60'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
