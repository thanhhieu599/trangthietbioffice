<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        abort_if(blank(config('admin.password')), 404);

        return $request->session()->get('admin_ok')
            ? redirect()->route('admin.banners.index')
            : view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        abort_if(blank(config('admin.password')), 404);

        $request->validate(['password' => ['required', 'string']]);

        if (! hash_equals((string) config('admin.password'), (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Mật khẩu không đúng.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_ok', true);

        return redirect()->intended(route('admin.banners.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_ok');
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
