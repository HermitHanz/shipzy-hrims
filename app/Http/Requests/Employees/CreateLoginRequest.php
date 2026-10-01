<?php
namespace App\Http\Requests\Employees;
use Illuminate\Foundation\Http\FormRequest;

class CreateLoginRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['email' => ['nullable', 'email', 'max:255', 'unique:users,email']];
    }
}