<?php
namespace App\Http\Requests\Employees;
use Illuminate\Foundation\Http\FormRequest;

class OnboardingRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            ...PersonalDetailsRequest::RULES,
            'personal_email' => ['required', 'email', 'max:255'],
            'phone'          => ['required', 'string', 'max:30'],
            'birth_date'     => ['required', 'date', 'before:today'],
            'address_line1'  => ['required', 'string', 'max:255'],
            'city'           => ['required', 'string', 'max:100'],
            'province'       => ['required', 'string', 'max:100'],
            'postal_code'    => ['required', 'string', 'max:20'],
            'accept_privacy' => ['accepted'],
        ];
    }
    public function messages(): array { return ['accept_privacy.accepted' => 'You need to acknowledge the data privacy notice to continue.']; }
}