<?php

namespace App\Http\Requests\Modules;

use Illuminate\Foundation\Http\FormRequest;

class ReorderModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'modules' => ['required', 'array', 'min:1'],
            'modules.*.id' => ['required', 'integer', 'exists:modules,id'],
            'modules.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
