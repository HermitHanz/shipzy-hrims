<?php

namespace App\Events;

use App\Models\User;

class ModuleUnlockAttempted
{
    public function __construct(
        public User $user,
        public string $module,
        public bool $successful,
        public ?string $ip = null,
        public bool $throttled = false,
    ) {}
}