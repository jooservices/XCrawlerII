<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchQualityPublishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'in:jav,actor'],
            'identifier' => ['required', 'string', 'max:255'],
            'identifier_mode' => ['nullable', 'in:auto,id,uuid'],
            'reindex_related' => ['nullable', 'boolean'],
        ];
    }

    public function entityType(): string
    {
        return (string) $this->validated('entity_type');
    }

    public function identifier(): string
    {
        return (string) $this->validated('identifier');
    }

    public function identifierMode(): string
    {
        return (string) $this->validated('identifier_mode', 'auto');
    }

    public function reindexRelated(): bool
    {
        return (bool) $this->validated('reindex_related', false);
    }
}
