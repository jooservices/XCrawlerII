<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RequestSyncRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin can request sync
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', 'in:onejav,141jav,ffjav,xcity'],
            'type' => ['required', 'in:new,popular,daily,tags,idols'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $source = (string) $this->input('source');
            $type = (string) $this->input('type');

            if ($source === 'xcity' && $type !== 'idols') {
                $validator->errors()->add('type', 'xcity only supports type: idols');
            }

            if ($source !== 'xcity' && $type === 'idols') {
                $validator->errors()->add('type', 'idols type only supports source: xcity');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source.in' => 'Invalid source. Allowed values: onejav, 141jav, ffjav, xcity',
            'type.in' => 'Invalid type. Allowed values: new, popular, daily, tags, idols',
        ];
    }
}
