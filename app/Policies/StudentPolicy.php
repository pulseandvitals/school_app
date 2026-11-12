<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Student $student): Response
    {
        return $student->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function delete(User $user, Student $student): Response
    {
        return $student->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
