<?php

namespace Modules\Udemy\Console\Traits;

use Modules\Udemy\Models\UserToken;

trait THasToken
{
    protected function getToken(string $token): UserToken
    {
        if ($token) {
            throw new \RuntimeException('Token is required');
        }

        return UserToken::updateOrCreate([
            'token' => $token
        ]);
    }
}
