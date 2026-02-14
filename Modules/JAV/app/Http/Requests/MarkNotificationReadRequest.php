<?php

namespace Modules\JAV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\JAV\Models\UserLikeNotification;

class MarkNotificationReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $notification = $this->route('notification');

        return $user !== null
            && $notification instanceof UserLikeNotification
            && $notification->user_id === (int) $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
