<?php

namespace App\Actions\Organization;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetBranchStatus extends OrganizationAction
{
    /**
     * An inactive branch can't receive new employees. It can only be deactivated once everyone
     * assigned to it has been moved or has separated, so nobody is left in a closed branch.
     */
    public function handle(User $actor, Branch $branch, bool $active): Branch
    {
        $this->authorize($actor, 'deactivate', $branch);

        if ($branch->is_active === $active) {
            return $branch;
        }

        if (! $active) {
            $count = $this->activeEmployeeCount('branch_id', $branch->id);

            if ($count > 0) {
                $this->reject('is_active', "Reassign the {$count} active employee(s) in this branch first.");
            }
        }

        DB::transaction(function () use ($actor, $branch, $active) {
            $branch->forceFill(['is_active' => $active])->save();

            $this->audit->log('branch.status_changed', $branch, ['is_active' => ! $active], ['is_active' => $active], $actor);
        });

        return $branch;
    }
}