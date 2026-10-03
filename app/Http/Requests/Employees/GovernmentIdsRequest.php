<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

class GovernmentIdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // ('sometimes': an absent key must stay absent so the action leaves it unchanged)
    public function rules(): array
    {
        $rule = ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[0-9\- ]*$/'];

        return ['sss' => $rule, 'tin' => $rule, 'philhealth' => $rule, 'pagibig' => $rule];
    }

    public function messages(): array
    {
        return ['*.regex' => 'Use digits, spaces and hyphens only.'];
    }
}
