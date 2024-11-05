<?php

namespace Modules\Udemy\Console\Traits;

use Modules\Udemy\Models\UserToken;

trait THasToken
{
    protected function getToken(): UserToken
    {
        return UserToken::updateOrCreate([
            'token' => $this->argument('token'),
        ]);
    }
}
