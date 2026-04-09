<?php

namespace App\Http\Controllers;

use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyCertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificates,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Certificate::class);

        return $this->success(
            CertificateResource::collection(
                $this->certificates->listCertificatesForUser($request->user()),
            )->resolve(),
        );
    }

    public function download(Request $request, int $id): JsonResponse
    {
        $certificate = $this->certificates->findCertificateForUser($request->user(), $id);
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
}
