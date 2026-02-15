<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $currentUserEmail = strtolower((string) ($this->user()?->email ?? ''));
        $incomingEmail = strtolower((string) $this->input('email', ''));
        $isEmailChanged = $currentUserEmail !== '' && $incomingEmail !== '' && $currentUserEmail !== $incomingEmail;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'current_password' => $isEmailChanged
                ? ['required', 'current_password']
                : ['nullable', 'current_password'],
        ];
    }
}
