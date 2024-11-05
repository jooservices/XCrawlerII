<?php

namespace Modules\Udemy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UdemyCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'token' => 'string|required|unique:user_tokens,token',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
