<?php

namespace App\Http\Requests\People;

use App\Domain\People\Enums\EmploymentType;
use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\TeacherStatus;
use App\Http\Requests\SchoolRequest;
use App\Support\PhoneNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TeacherRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'staff_code' => ['nullable', 'string', 'max:30', $this->uniqueInSchool('teachers', 'staff_code', $this->route('teacher'))],
            'name_km' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'dob' => ['nullable', 'date', 'after:1940-01-01', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'hired_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(TeacherStatus::class)],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'distinct', $this->existsInSchool('subjects')],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->filled('phone') && PhoneNumber::normalize($this->input('phone')) === null) {
                $validator->errors()->add('phone', __('validation.phone'));
            }
        }];
    }

    /** @return array<string, mixed> */
    public function teacher(): array
    {
        return [
            ...$this->safe()->except(['subject_ids', 'phone', 'email']),
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'email' => $this->filled('email') ? strtolower((string) $this->input('email')) : null,
        ];
    }

    /** @return list<int> */
    public function subjectIds(): array
    {
        return array_map('intval', $this->validated('subject_ids', []));
    }
}
