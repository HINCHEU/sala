<?php

namespace App\Http\Requests\People;

use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\IdPoorLevel;
use App\Domain\People\Enums\StudentStatus;
use App\Domain\People\Provinces;
use App\Http\Requests\SchoolRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'student_code' => ['nullable', 'string', 'max:30', $this->uniqueInSchool('students', 'student_code', $this->route('student'))],
            'last_name_km' => ['required', 'string', 'max:100'],
            'first_name_km' => ['required', 'string', 'max:100'],
            'last_name_en' => ['nullable', 'string', 'max:100'],
            'first_name_en' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'dob' => ['nullable', 'date', 'after:1950-01-01', 'before:today'],
            'birth_village' => ['nullable', 'string', 'max:100'],
            'birth_commune' => ['nullable', 'string', 'max:100'],
            'birth_district' => ['nullable', 'string', 'max:100'],
            'birth_province' => ['nullable', Rule::in(array_keys(Provinces::ALL))],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_cert_no' => ['nullable', 'string', 'max:50'],
            'id_poor_level' => ['nullable', Rule::enum(IdPoorLevel::class)],
            'disability' => ['nullable', 'string', 'max:500'],
            'admitted_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /** @return array<string, mixed> */
    public function student(): array
    {
        return $this->safe()->except('photo');
    }
}
