<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;


class UpdateUserRolesRequest extends FormRequest{
    
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['roles' => ['nullable', 'array'], 'roles.*' => ['string', 'exists:roles,name']];
    }
}