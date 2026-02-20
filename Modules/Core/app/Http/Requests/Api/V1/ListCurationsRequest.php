<?php

namespace Modules\Core\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCurationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $itemModels = (array) config('core.curations.item_models', []);
        $curationTypes = (array) config('core.curations.types', []);

        return [
            'item_type' => ['nullable', 'string', Rule::in(array_keys($itemModels))],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'curation_type' => ['nullable', 'string', Rule::in($curationTypes)],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
