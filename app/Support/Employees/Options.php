<?php

namespace App\Support\Employees;

use App\Actions\Employees\EmployeeAction;
use Illuminate\Support\Str;

class Options
{
    public static function types(): array
    {
        return self::normalize(EmployeeAction::EMPLOYMENT_TYPES);
    }

    public static function statuses(): array
    {
        return self::normalize(EmployeeAction::STATUSES);
    }

    private static function normalize(array $items): array
    {
        $out = [];
        foreach ($items as $key => $value) {
            is_int($key) ? $out[$value] = Str::headline($value) : $out[$key] = $value;
        }

        return $out;
    }
}
