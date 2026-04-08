<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;

class CoursePolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('courses.view');
    }

    public function view(User $user, Course $course): bool
    {
        return $this->hasTenantPermission($user, 'courses.view', $course->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('courses.create');
    }

    public function update(User $user, Course $course): bool
    {
        return $this->hasTenantPermission($user, 'courses.update', $course->tenant_id);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->hasTenantPermission($user, 'courses.delete', $course->tenant_id);
    }

    public function publish(User $user, Course $course): bool
    {
        return $this->hasTenantPermission($user, 'courses.publish', $course->tenant_id);
    }
}
