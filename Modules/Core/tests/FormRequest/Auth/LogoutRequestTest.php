<?php

declare(strict_types=1);

namespace Modules\Core\Tests\FormRequest\Auth;

use App\Models\User;
use Modules\Core\Http\Requests\Auth\LogoutRequest;
use Modules\Core\Tests\TestCase;

final class LogoutRequestTest extends TestCase
{
    public function test_authorize_returns_false_for_guest(): void
    {
        $request = LogoutRequest::create('/auth/logout', 'POST');
        $request->setUserResolver(fn () => null);

        $this->assertFalse($request->authorize());
    }

    public function test_authorize_returns_true_for_authenticated_user(): void
    {
        $user = User::factory()->make();
        $request = LogoutRequest::create('/auth/logout', 'POST');
        $request->setUserResolver(fn () => $user);

        $this->assertTrue($request->authorize());
    }

    public function test_rules_are_empty(): void
    {
        $request = new LogoutRequest();

        $this->assertSame([], $request->rules());
    }
}
