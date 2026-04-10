<?php

namespace App\Http\Requests\Packages;

use Illuminate\Foundation\Http\FormRequest;

class UploadPackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:zip', 'max:524288'],  // 512 MB
        ];
    }
}
