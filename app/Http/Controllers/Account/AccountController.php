<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Services\Sapo\SapoSync;
use App\Support\CustomerInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/** Trang tài khoản khách hàng: thông tin, mật khẩu, lịch sử đơn hàng. */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.account.show', [
            'user' => $request->user(),
            'recentOrders' => $request->user()->orders()->withCount('items')->take(3)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(CustomerInfo::rules() + [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], CustomerInfo::messages(), CustomerInfo::attributes());

        $user->update($data);
        SapoSync::customer($user);

        return back()->with('ok', 'Đã lưu thông tin tài khoản.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], CustomerInfo::messages() + ['password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.'], CustomerInfo::attributes());

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('ok', 'Đã đổi mật khẩu.');
    }

    public function orders(Request $request): View
    {
        return view('pages.account.orders', [
            'orders' => $request->user()->orders()->withCount('items')->paginate(15),
        ]);
    }

    public function order(Request $request, QuoteRequest $order): View
    {
        // Khách chỉ xem được đơn của chính mình.
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('pages.account.order', ['order' => $order->load('items.product')]);
    }
}
