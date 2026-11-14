<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\CaptchaController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Publik
Route::get('/', [HomeController::class, 'index'])->name('home');
// Katalog = beranda. Alamat lama tetap jalan (filter ikut dibawa).
Route::get('/produk', fn (\Illuminate\Http\Request $r) => redirect()->route('home', $r->only(['q', 'kategori', 'urut'])))->name('products.index');
Route::get('/produk/{slug}', [ProductController::class, 'show'])->name('products.show');

// Tamu
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'create'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/daftar', [RegisterController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/captcha.png', [CaptchaController::class, 'show'])->middleware('throttle:30,1')->name('captcha');
    Route::get('/masuk/verifikasi', [TwoFactorChallengeController::class, 'show'])->name('login.2fa');
    Route::post('/masuk/verifikasi', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:10,1');
});

// Pengguna login
Route::middleware('auth')->group(function () {
    Route::post('/keluar', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/konfirmasi-password', [ConfirmPasswordController::class, 'show'])->name('password.confirm');
    Route::post('/konfirmasi-password', [ConfirmPasswordController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil/nama', [ProfileController::class, 'updateName'])->middleware('throttle:10,1')->name('profile.name');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:5,1')->name('profile.password');
});

// Pembeli
Route::middleware(['auth', 'role:pembeli'])->group(function () {
    Route::post('/checkout/{slug}', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/saldo', [BalanceController::class, 'index'])->name('balance.index');
    Route::post('/saldo/isi', [BalanceController::class, 'topup'])->middleware('throttle:10,1')->name('balance.topup');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{code}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{code}/akun', [OrderController::class, 'reveal'])->middleware('throttle:10,1')->name('orders.reveal');
    Route::get('/pesanan/{code}/bayar', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/pesanan/{code}/cek-pembayaran', [PaymentController::class, 'check'])->middleware('throttle:12,1')->name('payments.check');
    Route::post('/pesanan/{code}/simulasi-bayar', [PaymentController::class, 'simulate'])->name('payments.simulate');
});

// Webhook Casaku (tanpa CSRF, diverifikasi HMAC)
Route::post('/webhooks/casaku', [PaymentController::class, 'webhook'])->middleware('throttle:60,1')->name('webhooks.casaku');

// Admin
// Admin: setel 2FA (butuh konfirmasi ulang password), boleh diakses sebelum 2FA aktif
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin', 'reauth'])->group(function () {
    Route::get('/2fa', [Admin\TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa', [Admin\TwoFactorController::class, 'store'])->middleware('throttle:10,1')->name('2fa.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin', '2fa'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/produk', [Admin\ProductController::class, 'index'])->name('products.index');
    Route::get('/produk/baru', [Admin\ProductController::class, 'create'])->name('products.create');
    Route::post('/produk', [Admin\ProductController::class, 'store'])->name('products.store');
    Route::get('/produk/{product:slug}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/produk/{product:slug}', [Admin\ProductController::class, 'update'])->name('products.update');
    Route::post('/produk/{product:slug}/toggle', [Admin\ProductController::class, 'toggle'])->name('products.toggle');

    Route::get('/produk/{product:slug}/stok', [Admin\StockController::class, 'index'])->name('stock.index');
    Route::post('/produk/{product:slug}/stok', [Admin\StockController::class, 'store'])->name('stock.store');
    Route::post('/produk/{product:slug}/stok/{account}/nonaktif', [Admin\StockController::class, 'disable'])->name('stock.disable');

    Route::get('/pesanan', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order:code}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order:code}/tangani', [Admin\OrderController::class, 'resolve'])->middleware('reauth')->name('orders.resolve');

    Route::get('/pengguna', [Admin\UserController::class, 'index'])->name('users.index');
    Route::put('/pengguna/{user}/role', [Admin\UserController::class, 'updateRole'])->middleware('reauth')->name('users.role');
    Route::post('/pengguna/{user}/saldo', [Admin\UserController::class, 'credit'])->middleware(['reauth', 'throttle:20,1'])->name('users.credit');

    Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->name('audit.index');
});
