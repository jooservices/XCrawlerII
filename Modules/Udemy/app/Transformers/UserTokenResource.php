<?php

namespace Modules\Udemy\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $uuid
 * @property string $token
 * @property Carbon $created_at
 */
class UserTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'token' => $this->token,
            'created_at' => $this->created_at,
        ];
    }
}
