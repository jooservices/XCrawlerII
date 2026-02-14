<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetRatingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Anyone can view ratings
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jav_id' => ['sometimes', 'integer', 'exists:jav,id'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'sort' => ['sometimes', 'string', 'in:recent,highest,lowest'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
