<?php

namespace App\Support\Security;

use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class ModuleUnlock
{
    private const SESSION = 'module_unlocks';

    /** Module definition with defaults applied, or null if the key is unknown. */
    public function find(string $module): ?array
    {
        $def = config('protected_modules.modules', [])[$module] ?? null;

        if (! is_array($def)) {
            return null;
        }

        return $def + [
            'label'       => ucfirst(str_replace('-', ' ', $module)),
            'description' => null,
            'permissions' => [],
            'home'        => null,
            'ttl'         => (int) config('protected_modules.default_ttl', 15),
            'sliding'     => true,
        ];
    }

    /** Same, but a typo in a route fails loudly (closed), not silently open. */
    public function definition(string $module): array
    {
        return $this->find($module)
            ?? throw new InvalidArgumentException("Unknown protected module [{$module}]. Add it to config/protected_modules.php.");
    }

    public function isUnlocked(User $user, string $module): bool
    {
        return $this->expiresAt($user, $module) !== null;
    }

    /** When the unlock ends, or null if locked. Expired entries are removed. */
    public function expiresAt(User $user, string $module): ?Carbon
    {
        $def   = $this->find($module);
        $entry = session(self::SESSION . '.' . $module);

        if (! $def || ! is_array($entry) || (int) ($entry['user'] ?? 0) !== (int) $user->getAuthIdentifier()) {
            return null;
        }

        $expires = Carbon::createFromTimestamp((int) ($entry['at'] ?? 0))->addMinutes($def['ttl']);

        if ($expires->isPast()) {
            $this->lock($module);

            return null;
        }

        return $expires;
    }

    public function unlock(User $user, string $module): void
    {
        $this->definition($module);

        session()->put(self::SESSION . '.' . $module, [
            'user' => $user->getAuthIdentifier(),
            'at'   => now()->timestamp,
        ]);
    }

    /** Resets the idle timer (sliding modules only). */
    public function touch(User $user, string $module): void
    {
        if ($this->definition($module)['sliding'] && $this->isUnlocked($user, $module)) {
            $this->unlock($user, $module);
        }
    }

    public function lock(string $module): void
    {
        session()->forget(self::SESSION . '.' . $module);
    }

    public function lockAll(): void
    {
        session()->forget(self::SESSION);
    }
}