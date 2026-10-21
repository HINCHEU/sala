<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** An invitation goes to an email, a Cambodian phone number, or both. */
class InvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware authorises
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->filled('phone') && $this->phone() === null) {
                $validator->errors()->add('phone', __('validation.phone'));
            }
        }];
    }

    public function email(): ?string
    {
        return $this->filled('email') ? strtolower((string) $this->input('email')) : null;
    }

    public function phone(): ?string
    {
        return PhoneNumber::normalize($this->input('phone'));
    }
}
