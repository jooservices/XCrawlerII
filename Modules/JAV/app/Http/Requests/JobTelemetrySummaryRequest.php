<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobTelemetrySummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'window_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'site' => ['nullable', 'string', 'max:255'],
            'job_name' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:200'],
        ];
    }
}
