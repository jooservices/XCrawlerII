<?php

declare(strict_types=1);

namespace Modules\Core\Tests\FormRequest\Auth;

use Illuminate\Support\Facades\Validator;
use Modules\Core\Http\Requests\Auth\LoginRequest;
use Modules\Core\Tests\TestCase;

final class LoginRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new LoginRequest();

        $this->assertTrue($request->authorize());
    }

    public function test_rules_require_login_and_password(): void
    {
        $request = new LoginRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('login', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_rules_accept_valid_payload(): void
    {
        $request = new LoginRequest();

        $validator = Validator::make([
            'login' => 'user@example.test',
            'password' => 'strong-pass',
            'remember' => true,
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }
}
