<?php

namespace App\Http\Requests\Admin;

use App\Support\Audit\AuditCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AuditLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'actor_id' => ['nullable', 'regex:/^(system|\d+)$/'],
            'category' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:150'],
            'severity' => ['nullable', Rule::in(array_keys(AuditCatalog::SEVERITIES))],
            'target_type' => ['nullable', 'string', 'max:150'],
            'target_id' => ['nullable', 'string', 'max:64'],
            'ip' => ['nullable', 'string', 'max:45'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** Applied filters. The last 30 days are the default only when neither date was submitted. */
    public function filters(): array
    {
        $data = Arr::except($this->validated(), ['page']);

        if (! $this->has('from') && ! $this->has('to')) {
            $data['from'] = now()->subDays(30)->toDateString();
            $data['to'] = now()->toDateString();
        }

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
