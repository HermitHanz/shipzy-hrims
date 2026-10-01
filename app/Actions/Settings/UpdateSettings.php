<?php

namespace App\Actions\Settings;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Settings\Settings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateSettings
{
    public function __construct(private Settings $settings, private AuditLogger $audit)
    {
    }

    /**
     * Saves one group of settings using the rules in config/hrims_settings.php. A field that is
     * absent from $input is left alone, except booleans, where absent means unchecked (false).
     * Errors are keyed by field name. Every real change is written to the audit log.
     *
     * @param  array<string, mixed>  $input  field name => value (for example the group's form)
     * @return array<int, string>  names of the fields that actually changed
     */
    public function handle(User $actor, string $group, array $input): array
    {
        if (! $actor->can('system.settings.edit')) {
            throw new AuthorizationException('You cannot change settings.');
        }

        $fields = $this->settings->groups()[$group]['fields'] ?? null;

        if (! is_array($fields)) {
            throw ValidationException::withMessages(['group' => 'Unknown settings group.']);
        }

        $provided = [];
        $rules = [];

        foreach ($fields as $name => $definition) {
            if (($definition['type'] ?? 'text') === 'boolean') {
                $provided[$name] = filter_var($input[$name] ?? false, FILTER_VALIDATE_BOOLEAN);
            } elseif (array_key_exists($name, $input)) {
                $value = $input[$name];
                $provided[$name] = is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;
            } else {
                continue;
            }

            $rules[$name] = $definition['rules'] ?? ['nullable'];
        }

        $validated = Validator::make($provided, $rules)->validate();

        $changes = [];
        $old = [];
        $new = [];

        foreach ($validated as $name => $value) {
            $definition = $fields[$name];
            $typed = $this->settings->castValue($definition, $value);
            $current = $this->settings->get("{$group}.{$name}");

            if ($typed === $current) {
                continue;
            }

            $redact = ! empty($definition['sensitive']);

            $changes[$name] = $typed;
            $old["{$group}.{$name}"] = $redact ? '[redacted]' : $current;
            $new["{$group}.{$name}"] = $redact ? '[redacted]' : $typed;
        }

        if ($changes === []) {
            return [];
        }

        DB::transaction(function () use ($actor, $group, $changes, $old, $new) {
            foreach ($changes as $name => $value) {
                $this->settings->set("{$group}.{$name}", $value, $actor);
            }

            $this->audit->log('settings.updated', null, $old, $new, $actor);
        });

        return array_keys($changes);
    }
}