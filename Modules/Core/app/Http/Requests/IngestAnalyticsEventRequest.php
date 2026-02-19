<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;

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
            'domain' => ['required', 'string', Rule::enum(AnalyticsDomain::class)],
            'entity_type' => ['required', 'string', Rule::enum(AnalyticsEntityType::class)],
            'entity_id' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', Rule::enum(AnalyticsAction::class)],
            'value' => ['nullable', 'integer', 'min:1', 'max:100'],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
