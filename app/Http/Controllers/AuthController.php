<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok dengan data kampus.'])->onlyInput('email');
        }
        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->hasRole('mahasiswa')) {
            return redirect()->intended(route('portal.dashboard'));
        }

        if ($user->hasRole(['dosen', 'dosen_wali', 'dosen_pembimbing'])) {
            return redirect()->intended(route('lecturer.dashboard'));
        }

        return redirect()->intended('/admin');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
