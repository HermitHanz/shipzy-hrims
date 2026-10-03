<?php

namespace App\Http\Requests\Employees;

use App\Support\Employees\Options;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(Options::statuses()))],
            'separation_date' => ['required_if:status,separated', 'nullable', 'date'],
            'separation_reason' => ['required_if:status,separated', 'nullable', 'string', 'max:500'],
        ];
    }
}
