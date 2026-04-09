<?php

namespace App\Http\Requests\Media;

use App\Enums\MediaVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'visibility' => [
                'nullable',
                'string',
                Rule::in(array_map(
                    static fn (MediaVisibility $visibility) => $visibility->value,
                    MediaVisibility::cases(),
                )),
            ],
        ];
    }
}
