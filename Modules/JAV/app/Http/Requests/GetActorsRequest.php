<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetActorsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public access
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:255'],
            'tags_mode' => ['nullable', 'string', 'in:any,all'],
            'age' => ['nullable', 'integer', 'min:18', 'max:99'],
            'age_min' => ['nullable', 'integer', 'min:18', 'max:99'],
            'age_max' => ['nullable', 'integer', 'min:18', 'max:99'],
            'bio_key' => ['nullable', 'string', 'max:255'],
            'bio_value' => ['nullable', 'string', 'max:255'],
            'bio_filters' => ['nullable', 'array'],
            'bio_filters.*.key' => ['nullable', 'string', 'max:255'],
            'bio_filters.*.value' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:javs_count,name,created_at,updated_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
