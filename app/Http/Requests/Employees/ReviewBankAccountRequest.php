<?php
namespace App\Http\Requests\Employees;
use Illuminate\Foundation\Http\FormRequest;

class ReviewBankAccountRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'decision'   => ['required', 'in:verify,reject'],
            'reason'     => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
            'reject_for' => ['nullable', 'integer'],
        ];
    }
}