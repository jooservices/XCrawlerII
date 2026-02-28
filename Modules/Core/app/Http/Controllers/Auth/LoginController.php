<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Http\Requests\Auth\LoginRequest;
use Modules\Core\Http\Requests\Auth\LogoutRequest;
use Modules\Core\Services\AuthService;

final class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function renderLogin(): InertiaResponse
    {
        return Inertia::render('Core/auth/LoginPage');
    }

    public function actionLogin(LoginRequest $request): RedirectResponse
    {
        $this->authService->login($request->validated(), $request->session());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function actionLogout(LogoutRequest $request): RedirectResponse
    {
        $this->authService->logout($request->user(), $request->session());

        return redirect('/');
    }
}
