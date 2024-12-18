<?php

namespace Modules\Udemy\Console\Traits;

use Modules\Udemy\Models\UserToken;

trait THasToken
{
    final protected function getToken(string $token): UserToken
    {
        return UserToken::updateOrCreate([
            'token' => $token,
        ]);
    }
}
