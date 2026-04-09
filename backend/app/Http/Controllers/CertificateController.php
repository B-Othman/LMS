<?php

namespace App\Http\Controllers;

use App\Http\Requests\Certificates\IndexCertificatesRequest;
use App\Http\Requests\Certificates\RevokeCertificateRequest;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificates,
    ) {}

    public function index(IndexCertificatesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Certificate::class);

        $paginator = $this->certificates->paginateCertificates($request->validated());

        return $this->success(
            CertificateResource::collection($paginator->getCollection())->resolve(),
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        );
    }

    public function download(int $id): JsonResponse
    {
        $certificate = $this->certificates->findCertificate($id);
        $this->authorize('download', $certificate);

        try {
            $payload = $this->certificates->createDownloadPayload($certificate);
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'certificate_download_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success($payload);
    }

    public function revoke(RevokeCertificateRequest $request, int $id): JsonResponse
    {
        $certificate = $this->certificates->findCertificate($id);
        $this->authorize('revoke', $certificate);

        try {
            $certificate = $this->certificates->revoke(
                $certificate,
                (string) $request->validated('reason'),
                $request->user()->id,
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), 422, [
                ['code' => 'certificate_invalid', 'message' => $exception->getMessage()],
            ]);
        }

        return $this->success(
            new CertificateResource($certificate),
            'Certificate revoked successfully.',
        );
    }
}
