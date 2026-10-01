<?php
namespace App\Http\Requests\Employees;
use Illuminate\Foundation\Http\FormRequest;

class EmergencyContactsRequest extends FormRequest{
    
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'contacts'                => ['nullable', 'array', 'max:3'],
            'contacts.*.name'         => ['required', 'string', 'max:150'],
            'contacts.*.relationship' => ['required', 'string', 'max:100'],
            'contacts.*.phone'        => ['required', 'string', 'max:30'],
            'contacts.*.alt_phone'    => ['nullable', 'string', 'max:30'],
            'contacts.*.address'      => ['nullable', 'string', 'max:255'],
            'contacts.*.is_primary'   => ['nullable', 'boolean'],
        ];
    }
    public function attributes(): array
    {
        return [
            'contacts.*.name' => 'name of contact :position', 'contacts.*.relationship' => 'relationship of contact :position',
            'contacts.*.phone' => 'phone of contact :position',
        ];
    }
}