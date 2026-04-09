<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;

class EnrollmentPolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enrollments.view') || $user->hasRole('learner');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->id === $enrollment->user_id) {
            return true;
        }

        return $this->hasTenantPermission($user, 'enrollments.view', $enrollment->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('enrollments.create');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $this->hasTenantPermission($user, 'enrollments.delete', $enrollment->tenant_id);
    }
}
