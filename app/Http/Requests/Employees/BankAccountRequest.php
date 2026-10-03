<?php

namespace App\Http\Requests\Employees;

use App\Models\EmployeeBankAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:100'],
            'account_type' => ['required', Rule::in(array_keys(EmployeeBankAccount::ACCOUNT_TYPES))],
            'account_name' => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9\- ]+$/'],
        ];
    }

    public function messages(): array
    {
        return ['account_number.regex' => 'Use digits, spaces and hyphens only.'];
    }
}
