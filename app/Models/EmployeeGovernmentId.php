<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Numbers are encrypted at rest and never serialized. Display the masked value; the full number
 * comes only from RevealSensitiveField (permission checked and audited) or a payroll/reporting
 * service that does the same. Do NOT read the *_number attributes anywhere else.
 */
class EmployeeGovernmentId extends Model
{
    public const TYPES = ['sss', 'tin', 'philhealth', 'pagibig'];

    public const LABELS = ['sss' => 'SSS', 'tin' => 'TIN', 'philhealth' => 'PhilHealth', 'pagibig' => 'Pag-IBIG'];

    /** Accepted digit counts once punctuation is stripped. Adjust if your records differ. */
    public const LENGTHS = ['sss' => [10], 'tin' => [9, 12], 'philhealth' => [12], 'pagibig' => [12]];

    protected $fillable = ['employee_id'];

    protected $hidden = [
        'sss_number', 'sss_hash',
        'tin_number', 'tin_hash',
        'philhealth_number', 'philhealth_hash',
        'pagibig_number', 'pagibig_hash',
    ];

    protected $casts = [
        'sss_number' => 'encrypted',
        'tin_number' => 'encrypted',
        'philhealth_number' => 'encrypted',
        'pagibig_number' => 'encrypted',
    ];

    public static function normalize(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    public static function hashFor(string $type, string $digits): string
    {
        $key = config('hrims.pii_hash_key');

        if (! $key) {
            throw new RuntimeException('HRIMS_PII_HASH_KEY is not configured.');
        }

        return hash_hmac('sha256', $type . ':' . $digits, $key);
    }

    /** Store (or clear, when null) one ID together with its last4 and keyed hash. */
    public function setNumber(string $type, ?string $digits): void
    {
        if ($digits === null || $digits === '') {
            $this->{"{$type}_number"} = null;
            $this->{"{$type}_last4"} = null;
            $this->{"{$type}_hash"} = null;

            return;
        }

        $this->{"{$type}_number"} = $digits;
        $this->{"{$type}_last4"} = substr($digits, -4);
        $this->{"{$type}_hash"} = self::hashFor($type, $digits);
    }

    public function isSet(string $type): bool
    {
        return $this->{"{$type}_last4"} !== null;
    }

    public function masked(string $type): ?string
    {
        $last4 = $this->{"{$type}_last4"};

        return $last4 ? '••••••' . $last4 : null;
    }

    /** Full number. See the class note before calling this. */
    public function plain(string $type): ?string
    {
        return $this->{"{$type}_number"};
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}