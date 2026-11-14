<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLE_PEMBELI = 'pembeli';
    public const ROLE_ADMIN = 'admin';

    // "role" sengaja TIDAK fillable: nilai role dari formulir tidak pernah dipercaya.
    protected $fillable = ['name', 'username', 'password_hash'];

    protected $hidden = ['password_hash', 'totp_secret_enc', 'totp_last_step'];

    protected $attributes = ['role' => self::ROLE_PEMBELI];

    // Kolom hash password sesuai rancangan basis data.
    protected $authPasswordName = 'password_hash';

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'balance' => 'integer',
            'totp_enabled_at' => 'datetime',
            'totp_last_step' => 'integer',
        ];
    }

    public function getRememberTokenName()
    {
        return ''; // fitur "ingat saya" tidak dipakai
    }

    /** 2FA aktif (kolom totp_* tidak fillable; hanya diubah lewat App\Services\Totp). */
    public function hasTwoFactor(): bool
    {
        return $this->totp_enabled_at !== null && ! empty($this->totp_secret_enc);
    }

    /** Aturan password sesuai role: admin lebih panjang (target utama penyerang). */
    public function passwordRule(): \Illuminate\Validation\Rules\Password
    {
        return $this->isAdmin()
            ? \Illuminate\Validation\Rules\Password::min((int) config('enovaz.password_min.admin', 16))->letters()->numbers()
            : \Illuminate\Validation\Rules\Password::defaults();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Riwayat mutasi saldo. "balance" sengaja tidak fillable: hanya diubah lewat BalanceService. */
    public function ledger(): HasMany
    {
        return $this->hasMany(BalanceEntry::class);
    }
}
