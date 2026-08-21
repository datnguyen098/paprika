<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BranchAccess
{
    public static function apply(Builder $query, ?User $user): Builder
    {
        if ($user?->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public static function applyViaRelation(Builder $query, ?User $user, string $relation): Builder
    {
        if ($user?->branch_id) {
            $query->whereHas($relation, fn (Builder $query) => $query->where('branch_id', $user->branch_id));
        }

        return $query;
    }

    public static function authorize(?User $user, ?int $branchId): void
    {
        abort_unless($user?->canAccessBranch($branchId), 403, 'Ban khong co quyen xem du lieu cua co so nay.');
    }
}
