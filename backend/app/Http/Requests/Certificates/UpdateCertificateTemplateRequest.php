<?php

namespace App\Http\Requests\Certificates;

use App\Enums\CertificateTemplateLayout;
use App\Enums\CertificateTemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'layout' => ['sometimes', 'required', 'string', Rule::in(array_map(fn ($layout) => $layout->value, CertificateTemplateLayout::cases()))],
            'background_image' => ['nullable', 'file', 'max:10240'],
            'clear_background_image' => ['nullable', 'boolean'],
            'content_html' => ['sometimes', 'required', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['sometimes', 'required', 'string', Rule::in(array_map(fn ($status) => $status->value, CertificateTemplateStatus::cases()))],
        ];
    }
}
