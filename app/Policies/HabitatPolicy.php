<?php

namespace App\Policies;

use App\Models\Habitat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HabitatPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Habitat $habitat): Response
    {
        return $habitat->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * Covers editing the habitat and everything inside it: members,
     * assessments and scores.
     */
    public function update(User $user, Habitat $habitat): Response
    {
        return $habitat->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function delete(User $user, Habitat $habitat): Response
    {
        return $habitat->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
