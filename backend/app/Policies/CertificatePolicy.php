<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;

class CertificatePolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('certificates.view') || $user->hasPermission('certificates.issue');
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if ($user->id === $certificate->user_id) {
            return true;
        }

        return $this->hasTenantPermission($user, 'certificates.issue', $certificate->tenant_id);
    }

    public function download(User $user, Certificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }

    public function revoke(User $user, Certificate $certificate): bool
    {
        return $this->hasTenantPermission($user, 'certificates.issue', $certificate->tenant_id);
    }
}
