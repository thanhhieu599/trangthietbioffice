<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Sapo\SapoSync;
use App\Support\CustomerInfo;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/** Đăng nhập, đăng ký, đăng xuất, quên mật khẩu của khách hàng. */
class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        // Quay lại trang đang xem (VD: bước đặt hàng) sau khi đăng nhập.
        if ($back = $this->safeBack($request)) {
            $request->session()->put('url.intended', $back);
        }

        return view('pages.account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], CustomerInfo::messages(), CustomerInfo::attributes());

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.show'))->with('ok', 'Đăng nhập thành công.');
    }

    public function showRegister(Request $request): View
    {
        if ($back = $this->safeBack($request)) {
            $request->session()->put('url.intended', $back);
        }

        return view('pages.account.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(CustomerInfo::rules() + [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'terms' => ['accepted'],
        ], CustomerInfo::messages() + ['password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.'], CustomerInfo::attributes());

        $user = User::create(collect($data)->except('terms')->all());
        SapoSync::customer($user); // tạo khách hàng bên Sapo (chạy ngầm, nếu đã cấu hình)
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('account.show'))->with('ok', 'Đăng ký thành công. Chào mừng anh/chị đến với '.config('site.brand').'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /* ---------- Quên mật khẩu ---------- */

    public function showForgot(): View
    {
        return view('pages.account.forgot');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], CustomerInfo::messages(), CustomerInfo::attributes());

        Password::sendResetLink($request->only('email'));

        // Luôn trả cùng một thông báo: không để lộ email nào đã có tài khoản.
        return back()->with('ok', 'Nếu email này đã đăng ký, chúng tôi đã gửi liên kết đặt lại mật khẩu. Vui lòng kiểm tra hộp thư (cả mục Spam).');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('pages.account.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], CustomerInfo::messages() + ['password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.'], CustomerInfo::attributes());

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('ok', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.']);
    }

    /** Chỉ nhận đường dẫn nội bộ (chặn chuyển hướng ra trang ngoài). */
    private function safeBack(Request $request): ?string
    {
        $back = (string) $request->query('quay-lai');

        return preg_match('#^/(?!/)[\w\-/?=&%.]*$#', $back) ? $back : null;
    }
}
