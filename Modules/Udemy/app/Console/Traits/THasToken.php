<?php

namespace Modules\Udemy\Console\Traits;

use Modules\Udemy\Models\UserToken;

trait THasToken
{
    final protected function getToken(): UserToken
    {
        return UserToken::updateOrCreate([
            'token' => $this->ask(
                'Enter your Udemy token',
                config('udemy.token')
            ),
        ]);
    }
}
