<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AnalyticsApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dimension' => ['nullable', 'string', Rule::in(['age_bucket', 'blood_type', 'birthplace'])],
            'genre' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'integer', 'min:1', 'max:30'],
            'type' => ['nullable', 'string', Rule::in(['actor', 'genre', 'birthplace', 'blood_type'])],
            'q' => ['nullable', 'string', 'max:120'],
            'actor_uuid' => ['nullable', 'uuid'],
            'segment_type' => ['nullable', 'string', Rule::in(['age_bucket', 'blood_type', 'birthplace'])],
            'segment_value' => ['nullable', 'string', 'max:120'],
            'min_support' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'interval' => ['nullable', 'string', Rule::in(['week', 'month'])],
            'age' => ['nullable', 'integer', 'min:16', 'max:80'],
            'blood_type' => ['nullable', 'string', 'max:20'],
            'birthplace' => ['nullable', 'string', 'max:120'],
            'movie_tags' => ['nullable', 'array', 'max:10'],
            'movie_tags.*' => ['string', 'max:100'],
        ];
    }
}
