<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;


class StoreRoleRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:roles,name'],
            'label'         => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:255'],
            'level'         => ['required', 'integer', 'min:1', 'max:99'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
    public function messages(): array
    {
        return ['name.regex' => 'Use lowercase letters, numbers and hyphens only (e.g. team-lead).'];
    }
}