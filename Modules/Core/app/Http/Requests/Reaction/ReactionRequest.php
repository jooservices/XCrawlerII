<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Reaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReactionRequest extends FormRequest
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
        /** @var array<int, string> $allowedTypes */
        $allowedTypes = (array) config('core.reactions.allowed_types', []);

        return [
            'reactable_type' => ['required', 'string', 'max:255'],
            'reactable_id' => ['required', 'string', 'max:255'],
            'reaction' => ['required', 'string', 'max:16', Rule::in($allowedTypes)],
            'delta' => ['required', 'integer', Rule::in([-1, 5])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reactable_type' => trim((string) $this->input('reactable_type', '')),
            'reactable_id' => trim((string) $this->input('reactable_id', '')),
            'reaction' => strtolower(trim((string) $this->input('reaction', ''))),
            'delta' => (int) $this->input('delta', 0),
        ]);
    }
}
