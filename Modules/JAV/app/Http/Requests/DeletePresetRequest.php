<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeletePresetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'presetKey' => $this->route('presetKey'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'presetKey' => ['required', 'integer', 'min:0'],
        ];
    }
}
