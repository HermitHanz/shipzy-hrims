<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge(['group' => $this->route('group')]);
    }

    public function rules(): array
    {
        return ['group' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/']];
    }

    /** Only the group's own fields go to the action. */
    public function fields(): array
    {
        return $this->except(['_token', '_method', '_group', 'group']);
    }
}