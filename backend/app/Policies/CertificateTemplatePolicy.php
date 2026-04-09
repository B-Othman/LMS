<?php

namespace App\Policies;

use App\Models\CertificateTemplate;
use App\Models\User;
use App\Policies\Concerns\HandlesTenantAuthorization;

class CertificateTemplatePolicy
{
    use HandlesTenantAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('certificates.issue');
    }

    public function view(User $user, CertificateTemplate $template): bool
    {
        return $this->hasTenantPermission($user, 'certificates.issue', $template->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('certificates.issue');
    }

    public function update(User $user, CertificateTemplate $template): bool
    {
        return $this->hasTenantPermission($user, 'certificates.issue', $template->tenant_id);
    }

    public function delete(User $user, CertificateTemplate $template): bool
    {
        return $this->hasTenantPermission($user, 'certificates.issue', $template->tenant_id);
    }

    public function preview(User $user, CertificateTemplate $template): bool
    {
        return $this->hasTenantPermission($user, 'certificates.issue', $template->tenant_id);
    }
}
