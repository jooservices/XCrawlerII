<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    public function login(array $validated, Session $session): void
    {
        $loginInput = (string) ($validated['login'] ?? '');
        $password = (string) ($validated['password'] ?? '');
        $remember = (bool) ($validated['remember'] ?? false);

        $credentials = $this->resolveCredentials($loginInput, $password);

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials do not match our records.'],
            ]);
        }

        $session->regenerate();
    }

    public function logout(?Authenticatable $user, Session $session): void
    {
        if ($user !== null) {
            Auth::guard('web')->logout();
        }

        $session->invalidate();
        $session->regenerateToken();
    }

    /**
     * @return array<string, string>
     */
    private function resolveCredentials(string $loginInput, string $password): array
    {
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if ($loginField === 'username' && ! Schema::hasColumn('users', 'username')) {
            $loginField = 'email';
        }

        return [
            $loginField => $loginInput,
            'password' => $password,
        ];
    }
}
