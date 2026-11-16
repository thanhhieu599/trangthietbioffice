<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bảo vệ khu quản trị bằng mật khẩu đặt trong .env (ADMIN_PASSWORD).
 * Chưa đặt mật khẩu thì khu quản trị bị tắt hoàn toàn (trả về 404).
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(blank(config('admin.password')), 404);

        if (! $request->session()->get('admin_ok')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
