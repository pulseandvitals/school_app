<?php

namespace App\Policies;

use App\Models\GrowthRule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GrowthRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * System defaults are read-only; teachers edit their own copies.
     */
    public function update(User $user, GrowthRule $growthRule): Response
    {
        return $growthRule->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function delete(User $user, GrowthRule $growthRule): Response
    {
        return $growthRule->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
