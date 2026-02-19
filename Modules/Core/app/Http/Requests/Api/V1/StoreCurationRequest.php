<?php

namespace Modules\Core\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurationRequest extends FormRequest
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
            'item_type' => ['required', 'string', Rule::in(array_keys($itemModels))],
            'item_id' => ['required', 'integer', 'min:1'],
            'curation_type' => ['required', 'string', Rule::in($curationTypes)],
            'position' => ['nullable', 'integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
