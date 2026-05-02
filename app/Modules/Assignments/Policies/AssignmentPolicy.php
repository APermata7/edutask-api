<?php

namespace App\Modules\Assignments\Policies;

use App\Models\User;
use App\Modules\Assignments\Models\Assignment;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->isLecturer()) {
            return $assignment->lecturer_id === $user->id;
        }

        return $assignment->status === Assignment::STATUS_PUBLISHED;
    }

    public function create(User $user): bool
    {
        return $user->isLecturer();
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isLecturer() && $assignment->lecturer_id === $user->id;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->isLecturer() && $assignment->lecturer_id === $user->id;
    }

    public function publish(User $user, Assignment $assignment): bool
    {
        return $user->isLecturer() && $assignment->lecturer_id === $user->id;
    }
}