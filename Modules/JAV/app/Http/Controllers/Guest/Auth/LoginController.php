<?php

namespace Modules\JAV\Http\Controllers\Guest\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LoginController extends Controller
{
    public function showLoginFormVue(): InertiaResponse
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $login = $request->input('login', $request->input('username'));

        $request->validate([
            'login' => ['required_without:username'],
            'username' => ['required_without:login'],
            'password' => ['required'],
        ]);

        $loginInput = (string) $login;
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginField => $loginInput,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('jav.vue.dashboard'));
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput(['login', 'username']);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('jav.vue.dashboard');
    }
}
