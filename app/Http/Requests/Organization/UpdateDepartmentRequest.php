<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** The code is not editable, so it isn't accepted here. */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'head_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
