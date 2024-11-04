<?php

namespace Modules\Udemy\Repositories;

use Modules\Udemy\Models\UserToken;

class UserTokenRepository
{
    public function create(array $data): UserToken
    {
        return UserToken::updateOrCreate($data);
    }
}
