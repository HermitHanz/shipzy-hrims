<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnlockModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string', 'max:255']];
    }

    public function messages(): array
    {
        return ['password.required' => 'Enter your password to continue.'];
    }
}
