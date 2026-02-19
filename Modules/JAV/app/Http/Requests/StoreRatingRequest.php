<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must be authenticated to rate
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jav_id' => [
                'nullable',
                'integer',
                'exists:jav,id',
                'required_without:tag_id',
                fn ($attribute, $value, $fail) => $this->filled('tag_id') && $fail('Provide only one rating target: movie or tag.'),
            ],
            'tag_id' => [
                'nullable',
                'integer',
                'exists:tags,id',
                'required_without:jav_id',
                fn ($attribute, $value, $fail) => $this->filled('jav_id') && $fail('Provide only one rating target: movie or tag.'),
            ],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'jav_id.required_without' => 'Movie ID is required when tag ID is not provided.',
            'jav_id.exists' => 'The selected movie does not exist.',
            'jav_id.prohibited_with' => 'Provide only one rating target: movie or tag.',
            'tag_id.required_without' => 'Tag ID is required when movie ID is not provided.',
            'tag_id.exists' => 'The selected tag does not exist.',
            'tag_id.prohibited_with' => 'Provide only one rating target: movie or tag.',
            'rating.required' => 'Rating is required.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'review.max' => 'Review cannot exceed 1000 characters.',
        ];
    }
}
