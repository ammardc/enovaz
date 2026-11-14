<?php

use App\Models\User;
use App\Services\Audit;
use App\Services\CredentialVault;
use App\Services\PaymentService;
use App\Services\Totp;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('payments:expire', function (PaymentService $payments) {
    $released = $payments->expireOverdue();
    $this->info("{$released} tagihan kedaluwarsa, reservasi stok dilepas.");
})->purpose('Melepas reservasi stok dari tagihan QRIS yang kedaluwarsa dan dipastikan belum dibayar');

Artisan::command('enovaz:credential-key', function () {
    $this->line('Salin baris berikut ke file .env (simpan juga cadangannya di tempat aman):');
    $this->newLine();
    $this->info('CREDENTIAL_KEY='.CredentialVault::generateKey());
    $this->newLine();
    $this->warn('Jika kunci ini hilang atau diganti, kredensial stok yang sudah tersimpan tidak bisa dibuka lagi.');
})->purpose('Membuat kunci enkripsi kredensial produk');

Artisan::command('enovaz:2fa-reset {username}', function (string $username) {
    // Hanya dari terminal server (bukan web): untuk admin yang kehilangan HP authenticator.
    $user = User::where('username', strtolower($username))->first();
    if (! $user) {
        $this->error("User {$username} tidak ditemukan.");

        return 1;
    }
    app(Totp::class)->disable($user);
    Audit::log('admin.2fa_reset_cli', $user);
    $this->info("2FA {$user->username} direset. Saat login berikutnya, admin wajib menyetel ulang.");
})->purpose('Reset 2FA seorang pengguna (mis. HP hilang)');

Schedule::command('payments:expire')->everyMinute()->withoutOverlapping();
