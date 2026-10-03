<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

class PersonalDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public const RULES = [
        'personal_email' => ['nullable', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:30'],
        'birth_date' => ['nullable', 'date', 'before:today'],
        'address_line1' => ['nullable', 'string', 'max:255'],
        'address_line2' => ['nullable', 'string', 'max:255'],
        'barangay' => ['nullable', 'string', 'max:100'],
        'city' => ['nullable', 'string', 'max:100'],
        'province' => ['nullable', 'string', 'max:100'],
        'postal_code' => ['nullable', 'string', 'max:20'],
    ];

    public function rules(): array
    {
        return self::RULES;
    }
}
