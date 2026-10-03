<?php

namespace App\Http\Requests\Employees;

use App\Support\Employees\Options;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // StoreEmployeeRequest.php
    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50', 'unique:employees,employee_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'work_email' => ['nullable', 'email', 'max:255', 'unique:employees,work_email'],
            'job_title' => ['required', 'string', 'max:150'],
            'employment_type' => ['required', Rule::in(array_keys(Options::types()))],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
            'hire_date' => ['required', 'date'],
            'regularization_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            ...PersonalDetailsRequest::RULES,
        ];
    }
}
