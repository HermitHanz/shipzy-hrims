<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePersonalDetail extends Model
{
    /** Fields a person can fill in about themselves (phone lives on employees). */
    public const FIELDS = [
        'personal_email', 'birth_date',
        'address_line1', 'address_line2', 'barangay', 'city', 'province', 'postal_code',
    ];

    protected $fillable = [
        'employee_id',
        'personal_email', 'birth_date',
        'address_line1', 'address_line2', 'barangay', 'city', 'province', 'postal_code',
    ];

    protected $casts = ['birth_date' => 'date'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}