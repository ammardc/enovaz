<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminTwoFactorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('shop');
Route::get('/produk/{slug}', [CatalogController::class, 'show'])->name('products.show');
Route::get('/halaman/{slug}', [CatalogController::class, 'page'])->name('content.show');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/daftar', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/lupa-password', [AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'forgot'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});
Route::get('/auth/google', [AuthController::class, 'googleRedirect'])->name('google.redirect')->middleware('throttle:10,1');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('google.callback')->middleware('throttle:10,1');
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth','not.blocked'])->group(function () {
    Route::get('/akun', [AuthController::class, 'profile'])->name('profile');
    Route::post('/akun/password', [AuthController::class, 'changePassword'])->name('profile.password');
});

Route::middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{number}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{number}/akses', [OrderController::class, 'reveal'])->middleware('throttle:10,1')->name('orders.reveal');
    Route::post('/pesanan/{number}/refund', [RefundController::class, 'request'])->middleware('throttle:3,1')->name('orders.refund');
    Route::get('/checkout/{variant}', [OrderController::class, 'checkout'])->name('checkout.show');
    Route::post('/checkout/{variant}', [OrderController::class, 'place'])->middleware('throttle:6,1')->name('checkout.place');
    Route::get('/dompet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/dompet/riwayat-transaksi', [WalletController::class, 'transactions'])->name('wallet.transactions');
    Route::get('/dompet/riwayat-top-up', [WalletController::class, 'topups'])->name('wallet.topups');
    Route::get('/dompet/top-up', [WalletController::class, 'topupForm'])->name('wallet.topup');
    Route::post('/dompet/top-up', [WalletController::class, 'topup'])->middleware('throttle:4,1')->name('wallet.topup.store');
    Route::get('/tagihan/{reference}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/tagihan/{reference}/cek', [PaymentController::class, 'check'])->middleware('throttle:6,1')->name('payments.check');
});

Route::post('/webhook/casaku', [PaymentController::class, 'webhook'])->middleware('throttle:120,1')->name('casaku.webhook');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'not.blocked'])->group(function () {
    Route::get('/2fa', [AdminTwoFactorController::class, 'form'])->name('2fa');
    Route::post('/2fa', [AdminTwoFactorController::class, 'verify'])->middleware('throttle:5,1')->name('2fa.verify');
});
Route::prefix('admin')->name('admin.')->middleware(['auth', 'not.blocked', 'admin.2fa'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/kategori', [AdminController::class, 'categories'])->name('categories');
    Route::post('/kategori', [AdminController::class, 'createCategory'])->name('categories.create');
    Route::post('/kategori/{id}', [AdminController::class, 'saveCategory'])->name('categories.save');
    Route::get('/konten', [AdminController::class, 'content'])->name('content');
    Route::post('/konten/{slug}', [AdminController::class, 'saveContent'])->name('content.save');
    Route::get('/produk', [AdminController::class, 'products'])->name('products');
    Route::post('/produk', [AdminController::class, 'createProduct'])->name('products.create');
    Route::get('/produk/{id}', [AdminController::class, 'editProduct'])->name('products.edit');
    Route::post('/produk/{id}', [AdminController::class, 'saveProduct'])->name('products.save');
    Route::get('/produk/{id}/media', [AdminController::class, 'editMedia'])->name('products.media.edit');
    Route::post('/produk/{id}/media', [AdminController::class, 'uploadMedia'])->name('products.media');
    Route::get('/produk/{id}/varian', [AdminController::class, 'variants'])->name('products.variants');
    Route::post('/produk/{id}/varian', [AdminController::class, 'createVariant'])->name('products.variants.create');
    Route::post('/varian/{id}', [AdminController::class, 'saveVariant'])->name('variants.save');
    Route::post('/media/{id}/hapus', [AdminController::class, 'deleteMedia'])->name('media.delete');
    Route::get('/stok', [AdminController::class, 'stockIndex'])->name('stock.index');
    Route::get('/stok/{variant}', [AdminController::class, 'stock'])->name('stock');
    Route::post('/stok/{variant}/unit', [AdminController::class, 'addStock'])->name('stock.create');
    Route::post('/stok/{variant}', [AdminController::class, 'importStock'])->name('stock.import');
    Route::post('/stok/{variant}/unit/{unit}/status', [AdminController::class, 'changeStockStatus'])->name('stock.status');
    Route::post('/stok/{variant}/unit/{unit}/lihat', [AdminController::class, 'revealStock'])->name('stock.reveal');
    Route::get('/pesanan', [AdminController::class, 'orders'])->name('orders');
    Route::get('/pembayaran', [AdminController::class, 'payments'])->name('payments');
    Route::post('/pembayaran/{id}/cek', [AdminController::class, 'checkPayment'])->middleware('throttle:10,1')->name('payments.check');
    Route::get('/laporan', [AdminController::class, 'reports'])->name('reports');
    Route::get('/laporan/ekspor', [AdminController::class, 'exportReports'])->name('reports.export');
    Route::get('/audit', [AdminController::class, 'auditLogs'])->name('audit');
    Route::get('/refund', [AdminController::class, 'refunds'])->name('refunds');
    Route::post('/refund/{id}', [AdminController::class, 'decideRefund'])->name('refunds.decide');
    Route::get('/pelanggan', [AdminController::class, 'customers'])->name('customers');
    Route::post('/pelanggan/{id}/status', [AdminController::class, 'customerStatus'])->name('customers.status');
    Route::get('/koreksi-saldo', [AdminController::class, 'adjustments'])->name('adjustments');
    Route::post('/koreksi-saldo', [AdminController::class, 'requestAdjustment'])->name('adjustments.request');
    Route::post('/koreksi-saldo/{id}', [AdminController::class, 'decideAdjustment'])->name('adjustments.decide');
});
