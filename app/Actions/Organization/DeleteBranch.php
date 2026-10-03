<?php

namespace App\Actions\Organization;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteBranch extends OrganizationAction
{
    /** Only for branches created by mistake. Anything with employee records (even separated) must be deactivated instead. */
    public function handle(User $actor, Branch $branch): void
    {
        $this->authorize($actor, 'delete', $branch);

        if (Employee::withTrashed()->where('branch_id', $branch->id)->exists()) {
            $this->reject('branch', 'This branch has employee records. Deactivate it instead.');
        }

        DB::transaction(function () use ($actor, $branch) {
            $this->audit->log('branch.deleted', $branch, ['code' => $branch->code, 'name' => $branch->name], null, $actor);

            $branch->delete();
        });
    }
}