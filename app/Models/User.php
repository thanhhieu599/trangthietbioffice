<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Notifications\ResetPasswordVi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Tài khoản khách hàng (khu quản trị dùng mật khẩu riêng trong .env, không dùng bảng này). */
#[Fillable(['name', 'email', 'password', 'phone', 'company', 'tax_code', 'address', 'ward', 'province'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(QuoteRequest::class)->latest();
    }

    /** Địa chỉ giao hàng đầy đủ trên một dòng. */
    public function fullAddress(): string
    {
        return collect([$this->address, $this->ward, $this->province])->filter()->implode(', ');
    }

    /** Thông tin đã đủ để giao hàng chưa (dùng ở trang tài khoản và bước đặt hàng). */
    public function hasDeliveryInfo(): bool
    {
        return filled($this->phone) && filled($this->address) && filled($this->ward) && filled($this->province);
    }

    /** Email đặt lại mật khẩu bằng tiếng Việt. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordVi($token));
    }
}
