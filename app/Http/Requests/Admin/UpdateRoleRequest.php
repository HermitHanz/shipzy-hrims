<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;


class UpdateRoleRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    
    public function rules(): array
    {
        return [
            'label'         => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:255'],
            'level'         => ['required', 'integer', 'min:1', 'max:100'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}