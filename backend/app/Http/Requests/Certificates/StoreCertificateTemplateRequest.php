<?php

namespace App\Http\Requests\Certificates;

use App\Enums\CertificateTemplateLayout;
use App\Enums\CertificateTemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'layout' => ['nullable', 'string', Rule::in(array_map(fn ($layout) => $layout->value, CertificateTemplateLayout::cases()))],
            'background_image' => ['nullable', 'file', 'max:10240'],
            'content_html' => ['required', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(array_map(fn ($status) => $status->value, CertificateTemplateStatus::cases()))],
        ];
    }
}
