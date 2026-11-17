<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Email đặt lại mật khẩu cho khách hàng, bằng tiếng Việt. */
class ResetPasswordVi extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu – '.config('site.brand'))
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của anh/chị.')
            ->action('Đặt lại mật khẩu', $url)
            ->line("Liên kết có hiệu lực trong {$minutes} phút.")
            ->line('Nếu anh/chị không yêu cầu, hãy bỏ qua email này.')
            ->salutation(config('site.brand'));
    }
}
