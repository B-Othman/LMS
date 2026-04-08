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
        return $user->hasPermission('enrollments.view');
    }

    public function create(User $user, Enrollment $enrollment): bool
    {
        return $this->hasTenantPermission($user, 'enrollments.create', $enrollment->tenant_id);
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $this->hasTenantPermission($user, 'enrollments.delete', $enrollment->tenant_id);
    }
}
