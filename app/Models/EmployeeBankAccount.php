<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lifecycle: pending -> verified | rejected. A newer verified account marks the previous one
 * 'superseded'. Payroll may only use the single 'verified' account. The account number is
 * encrypted, never serialized, and only readable through RevealSensitiveField or a payroll
 * service that checks permission and audits.
 */
class EmployeeBankAccount extends Model
{
    public const PENDING = 'pending';
    public const VERIFIED = 'verified';
    public const REJECTED = 'rejected';
    public const SUPERSEDED = 'superseded';

    protected $fillable = [
        'employee_id', 'bank_name', 'account_name', 'status', 'rejection_reason',
        'submitted_by', 'reviewed_by', 'reviewed_at',
    ];

    protected $hidden = ['account_number'];

    protected $casts = [
        'account_number' => 'encrypted',
        'reviewed_at' => 'datetime',
    ];

    public function masked(): string
    {
        return '••••••' . $this->account_last4;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function submitter() { return $this->belongsTo(\App\Models\User::class, 'submitted_by'); }
    public function reviewer()  { return $this->belongsTo(\App\Models\User::class, 'reviewed_by'); }
}