<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Enums\AnalyticsDomain;

/**
 * Validate analytics ingest payload before handing off to hot-path counters.
 */
class IngestAnalyticsEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string', 'max:255'],
            // Relaxed max value for things like duration or batch counts
            'value' => ['nullable', 'integer', 'min:1', 'max:10000'],
            // Strict check against domain enum
            'domain' => ['required', 'string', Rule::enum(AnalyticsDomain::class)],
            'occurred_at' => ['required', 'date'],
            'user_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $providedUserId = $this->input('user_id');
            // If client sends user_id, it MUST match the auth user
            if ($providedUserId !== null && (int) $providedUserId !== (int) auth()->id()) {
                $validator->errors()->add('user_id', 'User ID mismatch.');
            }
        });
    }
}
