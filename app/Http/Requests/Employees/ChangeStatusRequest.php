<?php
namespace App\Http\Requests\Employees;
use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'status'            => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Employees\Options::statuses()))],
            'separation_date'   => ['required_if:status,separated', 'nullable', 'date'],
            'separation_reason' => ['required_if:status,separated', 'nullable', 'string', 'max:500'],
        ];
    }
}