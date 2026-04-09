<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Enums\CertificateTemplateStatus;
use App\Enums\EnrollmentStatus;
use App\Events\CertificateIssued;
use App\Jobs\GenerateCertificatePdfJob;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CertificateService
{
    /** @param array<string, mixed> $filters */
    public function paginateCertificates(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        if (! empty($filters['course_id'])) {
            $query->where('course_id', (int) $filters['course_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->whereHas('user', function (Builder $builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $this->applyStatusFilter($query, (string) $filters['status']);
        }

        if (! empty($filters['issued_from'])) {
            $query->whereDate('issued_at', '>=', $filters['issued_from']);
        }

        if (! empty($filters['issued_to'])) {
            $query->whereDate('issued_at', '<=', $filters['issued_to']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'issued_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortableColumns = ['issued_at', 'expires_at', 'created_at'];

        $query->orderBy(in_array($sortBy, $sortableColumns, true) ? $sortBy : 'issued_at', $sortDir);

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    /**
     * @return Collection<int, Certificate>
     */
    public function listCertificatesForUser(User $user): Collection
    {
        return $this->baseQuery()
            ->where('user_id', $user->id)
            ->orderByDesc('issued_at')
            ->get();
    }

    public function findCertificate(int $id): Certificate
    {
        return $this->baseQuery()->findOrFail($id);
    }

    public function findCertificateForUser(User $user, int $id): Certificate
    {
        return $this->baseQuery()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function findByVerificationCode(string $verificationCode): Certificate
    {
        return Certificate::withoutGlobalScopes()
            ->with(['user', 'course', 'template'])
            ->where('verification_code', strtoupper(trim($verificationCode)))
            ->firstOrFail();
    }

    public function issueCertificate(Enrollment $enrollment): ?Certificate
    {
        $enrollment->loadMissing('user', 'course.certificateTemplate');

        if ($enrollment->status !== EnrollmentStatus::Completed) {
            throw new \DomainException('Certificates can only be issued for completed enrollments.');
        }

        $existing = Certificate::withoutGlobalScopes()
            ->where('enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            return $existing->loadMissing('user', 'course', 'template');
        }

        $template = $this->resolveTemplateForCourse($enrollment->course);

        if (! $template) {
            return null;
        }

        return DB::transaction(function () use ($enrollment, $template) {
            $certificate = Certificate::query()->create([
                'tenant_id' => $enrollment->tenant_id,
                'enrollment_id' => $enrollment->id,
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
                'template_id' => $template->id,
                'issued_at' => now(),
                'verification_code' => $this->generateVerificationCode(),
                'metadata' => [
                    'learner_name' => $enrollment->user?->full_name,
                    'course_title' => $enrollment->course?->title,
                    'completion_date' => ($enrollment->completed_at ?? now())->format('F j, Y'),
                    'certificate_id' => null,
                ],
            ]);

            $certificate->forceFill([
                'metadata' => array_merge($certificate->metadata ?? [], [
                    'certificate_id' => 'CERT-'.$certificate->id,
                ]),
            ])->save();

            DB::afterCommit(function () use ($certificate) {
                GenerateCertificatePdfJob::dispatch($certificate->id)->afterCommit();
            });

            return $certificate->fresh(['user', 'course', 'template']) ?? $certificate;
        });
    }

    public function revoke(Certificate $certificate, string $reason, ?int $adminId = null): Certificate
    {
        if ($certificate->revoked_at !== null) {
            throw new \DomainException('This certificate has already been revoked.');
        }

        $certificate->forceFill([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
        ])->save();

        Log::info('Certificate revoked', [
            'certificate_id' => $certificate->id,
            'enrollment_id' => $certificate->enrollment_id,
            'user_id' => $certificate->user_id,
            'course_id' => $certificate->course_id,
            'admin_id' => $adminId,
            'reason' => $reason,
        ]);

        return $certificate->fresh(['user', 'course', 'template']) ?? $certificate;
    }

    /**
     * @return array{url: string, expires_at: string}
     */
    public function createDownloadPayload(Certificate $certificate): array
    {
        if (! $certificate->file_path) {
            throw new \DomainException('The certificate PDF is still being generated.');
        }

        $expiresAt = now()->addMinutes((int) config('certificates.signed_url_expiry_minutes', 15));
        $url = $certificate->downloadUrl(true, $expiresAt);

        if (! $url) {
            throw new \DomainException('The certificate PDF is not available right now.');
        }

        return [
            'url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function notifyIssued(Certificate $certificate): void
    {
        event(new CertificateIssued($certificate));
    }

    private function baseQuery(): Builder
    {
        return Certificate::query()->with(['user', 'course', 'template']);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            CertificateStatus::Active->value => $query->active(),
            CertificateStatus::Expired->value => $query->expired(),
            CertificateStatus::Revoked->value => $query->revoked(),
            default => null,
        };
    }

    private function resolveTemplateForCourse(?Course $course): ?CertificateTemplate
    {
        if (! $course) {
            return null;
        }

        $course->loadMissing('certificateTemplate');

        if (
            $course->certificateTemplate
            && $course->certificateTemplate->status === CertificateTemplateStatus::Active
        ) {
            return $course->certificateTemplate;
        }

        return CertificateTemplate::query()
            ->where('tenant_id', $course->tenant_id)
            ->where('is_default', true)
            ->where('status', CertificateTemplateStatus::Active->value)
            ->first();
    }

    private function generateVerificationCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (
            Certificate::withoutGlobalScopes()
                ->where('verification_code', $code)
                ->exists()
        );

        return $code;
    }
}
