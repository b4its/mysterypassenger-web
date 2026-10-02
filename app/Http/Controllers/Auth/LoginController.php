<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Tampilkan formulir masuk web non-Filament.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            return $user->isAdmin()
                ? redirect()->intended('/admin')
                : redirect()->intended(route('app.dashboard'));
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi pengguna web.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attemptCredentials = [
            $loginField => $credentials['login'],
            'password' => $credentials['password'],
        ];

        $remember = $request->boolean('remember');

        if (! Auth::attempt($attemptCredentials, $remember)) {
            throw ValidationException::withMessages([
                'login' => ['Kombinasi identitas atau kata sandi tidak cocok dengan data kami.'],
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => ['Akun Anda berstatus nonaktif. Silakan hubungi administrator.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended('/admin');
        }

        return redirect()->intended(route('app.dashboard'));
    }

    /**
     * Keluar dari sesi web.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('status', 'Anda telah berhasil keluar.');
    }
}
