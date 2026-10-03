<?php

namespace App\Actions\Organization;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateBranch extends OrganizationAction
{
    /** The code never changes after creation (other records and reports refer to it). */
    public function handle(User $actor, Branch $branch, array $data): Branch
    {
        $this->authorize($actor, 'update', $branch);

        $data = $this->clean($data);

        if (isset($data['code']) && strtoupper($data['code']) !== $branch->code) {
            $this->reject('code', 'The code cannot be changed.');
        }

        $name = $this->assertName($data['name'] ?? null);
        $address = array_key_exists('address', $data) ? $data['address'] : $branch->address;

        $old = ['name' => $branch->name, 'address' => $branch->address];

        $branch->fill(['name' => $name, 'address' => $address]);

        if (! $branch->isDirty()) {
            return $branch;
        }

        DB::transaction(function () use ($actor, $branch, $old, $name, $address) {
            $branch->save();

            $this->audit->log('branch.updated', $branch, $old, ['name' => $name, 'address' => $address], $actor);
        });

        return $branch;
    }
}