<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Đặt đầu tiên để URL gốc được cố định trước khi controller/view sinh link.
        $middleware->prepend(\App\Http\Middleware\SeoGuard::class);

        // Tài khoản khách hàng: chưa đăng nhập -> trang Đăng nhập; đã đăng nhập mà vào Đăng nhập/Đăng ký -> Tài khoản.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('account.show'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Link cũ (đổi slug / đã xóa) -> chuyển hướng 301 theo bảng redirects, giữ thứ hạng Google.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                return null;
            }
            try {
                $redirect = Redirect::where('from_path', Redirect::normalize($request->getPathInfo()))->first();
            } catch (\Throwable) {
                return null; // chưa chạy migrate
            }
            if (! $redirect) {
                return null;
            }
            $redirect->increment('hits', 1, ['last_hit_at' => now()]);
            $query = $request->getQueryString();

            return redirect($redirect->to_path.($query ? '?'.$query : ''), 301);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
