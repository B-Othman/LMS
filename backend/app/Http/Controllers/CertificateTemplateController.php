<?php

namespace App\Http\Controllers;

use App\Http\Requests\Certificates\StoreCertificateTemplateRequest;
use App\Http\Requests\Certificates\UpdateCertificateTemplateRequest;
use App\Http\Resources\CertificateTemplateResource;
use App\Models\CertificateTemplate;
use App\Services\CertificateTemplateService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CertificateTemplateController extends Controller
{
    public function __construct(
        private readonly CertificateTemplateService $templates,
        private readonly TenantContext $tenantContext,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', CertificateTemplate::class);

        return $this->success(
            CertificateTemplateResource::collection($this->templates->listTemplates())->resolve(),
        );
    }

    public function store(StoreCertificateTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', CertificateTemplate::class);

        $tenant = $this->tenantContext->tenant();

        if (! $tenant) {
            return $this->error('Tenant context is required.', 400, [
                ['code' => 'tenant_context_missing', 'message' => 'Tenant context is required.'],
            ]);
        }

        $template = $this->templates->createTemplate(
            $tenant,
            $request->user()->id,
            $request->validated(),
            $request->file('background_image'),
        );

        return $this->success(
            new CertificateTemplateResource($template),
            'Certificate template created successfully.',
            201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $template = $this->templates->findTemplate($id);
        $this->authorize('view', $template);

        return $this->success(new CertificateTemplateResource($template));
    }

    public function update(UpdateCertificateTemplateRequest $request, int $id): JsonResponse
    {
        $template = $this->templates->findTemplate($id);
        $this->authorize('update', $template);

        $template = $this->templates->updateTemplate(
            $template,
            $request->validated(),
            $request->file('background_image'),
        );

        return $this->success(
            new CertificateTemplateResource($template),
            'Certificate template updated successfully.',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $template = $this->templates->findTemplate($id);
        $this->authorize('delete', $template);

        try {
            $this->templates->deleteTemplate($template);
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'certificate_template_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(message: 'Certificate template deleted successfully.');
    }

    public function preview(int $id): Response
    {
        $template = $this->templates->findTemplate($id);
        $this->authorize('preview', $template);

        return response(
            $this->templates->renderPreviewPdf($template),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="certificate-template-preview.pdf"',
            ],
        );
    }
}
