<?php

namespace App\Actions\Organization;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBranch extends OrganizationAction
{
    /** @param  array{code: string, name: string, address?: string|null}  $data */
    public function handle(User $actor, array $data): Branch
    {
        $this->authorize($actor, 'create', Branch::class);

        $data = $this->clean($data);
        $code = $this->normalizeCode($data['code'] ?? null);
        $name = $this->assertName($data['name'] ?? null);

        if (Branch::where('code', $code)->exists()) {
            $this->reject('code', 'That code is already in use.');
        }

        return DB::transaction(function () use ($actor, $code, $name, $data) {
            $branch = Branch::create([
                'code' => $code,
                'name' => $name,
                'address' => $data['address'] ?? null,
                'is_active' => true,
            ]);

            $this->audit->log('branch.created', $branch, null, ['code' => $code, 'name' => $name], $actor);

            return $branch;
        });
    }
}