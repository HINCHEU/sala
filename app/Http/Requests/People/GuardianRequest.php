<?php

namespace App\Http\Requests\People;

use App\Domain\People\Enums\Gender;
use App\Domain\People\Enums\Relationship;
use App\Domain\People\Models\Guardian;
use App\Http\Requests\SchoolRequest;
use App\Support\PhoneNumber;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A guardian and how they relate to one student. Adding with a phone that a guardian already has links
 * that guardian (siblings), so the name is only needed for a new one. Editing cannot take another
 * guardian's phone.
 */
class GuardianRequest extends SchoolRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'name_km' => ['nullable', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'occupation' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'relationship' => ['required', Rule::enum(Relationship::class)],
            'is_primary_contact' => ['boolean'],
            'can_pickup' => ['boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            $phone = $this->phone();
            if ($this->filled('phone') && $phone === null) {
                $validator->errors()->add('phone', __('validation.phone'));

                return;
            }

            /** @var ?Guardian $editing */
            $editing = $this->route('guardian');
            $owner = $phone ? Guardian::where('phone', $phone)->first() : null;

            if ($editing && $owner && ! $owner->is($editing)) {
                $validator->errors()->add('phone', __('people.phone_taken', ['name' => $owner->name_km]));
            }
            if (! $this->filled('name_km') && ($editing || ! $owner)) {
                $validator->errors()->add('name_km', __('validation.required', ['attribute' => __('validation.attributes.name_km')]));
            }
        }];
    }

    public function phone(): ?string
    {
        return PhoneNumber::normalize($this->input('phone'));
    }

    /** @return array<string, mixed> */
    public function guardian(): array
    {
        return [
            ...$this->safe()->only(['name_km', 'name_en', 'gender', 'occupation', 'address']),
            'phone' => $this->phone(),
        ];
    }

    /** @return array{relationship: string, is_primary_contact: bool, can_pickup: bool} */
    public function link(): array
    {
        return [
            'relationship' => (string) $this->validated('relationship'),
            'is_primary_contact' => $this->boolean('is_primary_contact'),
            'can_pickup' => $this->boolean('can_pickup', true),
        ];
    }
}
