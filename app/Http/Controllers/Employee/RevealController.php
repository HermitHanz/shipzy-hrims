<?php

namespace App\Http\Controllers\Employee;
use App\Http\Controllers\Controller;
use App\Actions\Employees\RevealSensitiveField;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RevealController extends Controller
{
    public function store(Request $request, Employee $employee, RevealSensitiveField $action): JsonResponse
    {
        $data = $request->validate([
            'field'           => ['required', 'in:sss,tin,philhealth,pagibig,bank_account'],
            'bank_account_id' => ['nullable', 'integer'],
        ]);

        Gate::authorize($data['field'] === 'bank_account' ? 'revealBank' : 'revealGovernmentId', $employee);

        $value = $action->handle($request->user(), $employee, $data['field'], $data['bank_account_id'] ?? null);

        return response()->json(['value' => $value])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache');
    }
}