<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Enums\AnalyticsAction;

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
            'domain' => ['required', 'string', 'in:jav'],
            'entity_type' => ['required', 'string', 'in:movie,actor,tag'],
            'entity_id' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', Rule::enum(AnalyticsAction::class)],
            'value' => ['nullable', 'integer', 'min:1', 'max:100'],
            'occurred_at' => ['required', 'date_format:Y-m-d\\TH:i:s\\Z'],
        ];
    }
}
