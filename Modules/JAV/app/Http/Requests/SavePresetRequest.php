<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePresetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'q' => ['nullable', 'string', 'max:255'],
            'actor' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:255'],
            'tags_mode' => ['nullable', 'in:any,all'],
            'age' => ['nullable', 'integer', 'min:18', 'max:99'],
            'age_min' => ['nullable', 'integer', 'min:18', 'max:99'],
            'age_max' => ['nullable', 'integer', 'min:18', 'max:99'],
            'bio_key' => ['nullable', 'string', 'max:255'],
            'bio_value' => ['nullable', 'string', 'max:255'],
            'bio_filters' => ['nullable', 'array'],
            'bio_filters.*.key' => ['nullable', 'string', 'max:255'],
            'bio_filters.*.value' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:views,downloads,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'preset' => ['nullable', 'in:default,weekly_downloads,preferred_tags'],
        ];
    }
}
