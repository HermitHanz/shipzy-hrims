<?php

namespace App\Support\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Typed access to system settings. Definitions (labels, defaults, rules) live in
 * config/hrims_settings.php; only values that were changed are stored in the database.
 * A stored value wins, then the registry default, then the fallback you pass in.
 */
class Settings
{
    public const CACHE_KEY = 'hrims.settings';

    private ?array $stored = null;

    public static function value(string $key, mixed $default = null): mixed
    {
        return app(self::class)->get($key, $default);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $definition = $this->definition($key);
        $stored = $this->stored();

        if (array_key_exists($key, $stored)) {
            return $this->castValue($definition ?? [], $stored[$key]);
        }

        if ($definition !== null && array_key_exists('default', $definition)) {
            return $this->castValue($definition, $definition['default']);
        }

        return $default;
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function string(string $key, string $default = ''): string
    {
        return (string) ($this->get($key, $default) ?? $default);
    }

    /** @return array<string, array<string, mixed>> the registry, group key => definition */
    public function groups(): array
    {
        return config('hrims_settings.groups', []);
    }

    /** @return array<string, mixed>|null the field definition for 'group.name' */
    public function definition(string $key): ?array
    {
        [$group, $name] = array_pad(explode('.', $key, 2), 2, null);

        if ($group === null || $name === null) {
            return null;
        }

        return $this->groups()[$group]['fields'][$name] ?? null;
    }

    /** @return array<string, mixed> field name => current value, to prefill a group's form */
    public function forGroup(string $group): array
    {
        return collect($this->groups()[$group]['fields'] ?? [])
            ->mapWithKeys(fn (array $definition, string $name) => [$name => $this->get("{$group}.{$name}")])
            ->all();
    }

    public function set(string $key, mixed $value, ?User $by = null): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_by' => $by?->getKey()],
        );

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->stored = null;
    }

    /** Cast a raw value to the field's declared type. */
    public function castValue(array $definition, mixed $value): mixed
    {
        return match ($definition['type'] ?? 'text') {
            'number' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value === null ? null : (string) $value,
        };
    }

    private function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        try {
            return $this->stored = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => Setting::query()->pluck('value', 'key')
                    ->map(fn ($value) => json_decode((string) $value, true))
                    ->all(),
            );
        } catch (QueryException) {
            // The settings table doesn't exist yet (before migrating): fall back to defaults.
            return $this->stored = [];
        }
    }
}