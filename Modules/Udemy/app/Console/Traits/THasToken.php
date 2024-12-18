<?php

namespace Modules\Udemy\Console\Traits;

use Modules\Udemy\Models\UserToken;

trait THasToken
{
    protected function getToken(?string $token = null): UserToken
    {
        return UserToken::updateOrCreate([
            'token' => $token ?? $this->argument('token'),
        ]);
    }
}
