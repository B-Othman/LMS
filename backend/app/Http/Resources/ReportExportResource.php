<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_type' => $this->report_type,
            'format' => $this->format,
            'status' => $this->status,
            'filters' => $this->filters ?? [],
            'download_url' => $this->downloadUrl(),
            'error_message' => $this->error_message,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
