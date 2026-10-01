<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use ScopedByEmployee, SoftDeletes;

    protected $fillable = [
        'employee_number', 'first_name', 'middle_name', 'last_name',
        'work_email', 'phone',
        'branch_id', 'department_id', 'manager_id',
        'job_title', 'employment_type', 'status',
        'hire_date', 'regularization_date', 'separation_date', 'separation_reason',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'separation_date' => 'date',
        'regularization_date' => 'date',
        'onboarding_completed_at' => 'datetime',
        'privacy_acknowledged_at' => 'datetime',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim(
            $this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name
        ));
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    // ---- protected data (each has its own permissions, see EmployeePolicy) ----

    public function personalDetail(): HasOne
    {
        return $this->hasOne(EmployeePersonalDetail::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    public function governmentId(): HasOne
    {
        return $this->hasOne(EmployeeGovernmentId::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    /** The one verified account payroll may use (at most one exists; newer verification supersedes the old). */
    public function currentBankAccount(): HasOne
    {
        return $this->hasOne(EmployeeBankAccount::class)->where('status', EmployeeBankAccount::VERIFIED);
    }
}