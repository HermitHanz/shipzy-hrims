<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Read-only view of audit_logs. Entries are written by AuditLogger and can't be edited or
 * deleted through Eloquent. The retention command (audit:prune) removes old rows with the query builder,
 * over its own connection (hrims_audit.prune_connection). For stronger protection, point that at the
 * `audit_prune` connection and revoke UPDATE and DELETE on this table for the app's own database user.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}