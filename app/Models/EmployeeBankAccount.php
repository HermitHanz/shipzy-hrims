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

    /** value => label, for dropdowns. */
    public const ACCOUNT_TYPES = ['savings' => 'Savings', 'checking' => 'Checking', 'payroll' => 'Payroll'];

    protected $fillable = [
        'employee_id', 'bank_name', 'account_type', 'account_name', 'status', 'rejection_reason',
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

    /** Null for accounts submitted before account_type existed. */
    public function accountTypeLabel(): ?string
    {
        return self::ACCOUNT_TYPES[$this->account_type] ?? null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}