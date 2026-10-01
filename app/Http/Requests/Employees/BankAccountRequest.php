<?php
namespace App\Http\Requests\Employees;
use Illuminate\Foundation\Http\FormRequest;

class BankAccountRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_name'   => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9\- ]+$/'],
        ];
    }
    public function messages(): array { return ['account_number.regex' => 'Use digits, spaces and hyphens only.']; }
}