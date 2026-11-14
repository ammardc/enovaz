<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Delivery;
use App\Models\DigitalAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Casaku\CasakuGateway;
use App\Services\CredentialVault;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pendaftaran_selalu_menjadi_pembeli_walau_mengirim_role_admin(): void
    {
        $this->post('/daftar', [
            'captcha' => $this->solveCaptcha(),
            'name' => 'Penyerang',
            'username' => 'penyerang',
            'password' => 'RahasiaPanjang123',
            'password_confirmation' => 'RahasiaPanjang123',
            'role' => 'admin',
        ])->assertRedirect('/');

        $this->assertSame(User::ROLE_PEMBELI, User::where('username', 'penyerang')->first()->role);
    }

    public function test_password_disimpan_sebagai_hash_argon2id(): void
    {
        $user = $this->makeUser();
        $this->assertStringStartsWith('$argon2id$', $user->getRawOriginal('password_hash'));
    }

    public function test_login_dikunci_setelah_lima_kali_gagal(): void
    {
        $this->makeUser('korban');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['username' => 'korban', 'password' => 'salah'.$i, 'captcha' => $this->solveCaptcha()]);
        }
        $this->post('/masuk', ['username' => 'korban', 'password' => 'Rahasia123', 'captcha' => $this->solveCaptcha()])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'auth.login_locked')->exists());
    }

    public function test_pembeli_tidak_bisa_membuka_pesanan_orang_lain_idor(): void
    {
        $a = $this->makeUser('pembeli_a');
        $b = $this->makeUser('pembeli_b');
        $product = $this->makeProduct();

        $this->actingAs($a)->post("/checkout/{$product->slug}", ['quantity' => 1]);
        $order = Order::where('user_id', $a->id)->firstOrFail();

        $this->flushSession(); // pembeli B = browser lain
        $this->actingAs($b)->get("/pesanan/{$order->code}")->assertNotFound();
        $this->actingAs($b)->get("/pesanan/{$order->code}/bayar")->assertNotFound();
        $this->actingAs($b)->post("/pesanan/{$order->code}/akun")->assertNotFound();
    }

    public function test_pembeli_tidak_bisa_membuka_panel_admin(): void
    {
        $this->actingAs($this->makeUser())->get('/admin')->assertForbidden();
    }

    public function test_alur_bayar_menyerahkan_akun_satu_kali_walau_dicek_berulang(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(stock: 3);

        $this->actingAs($user)->post("/checkout/{$product->slug}", ['quantity' => 2])->assertRedirect();
        $order = Order::where('user_id', $user->id)->with('payment')->firstOrFail();

        $this->assertSame(2, DigitalAccount::where('status', DigitalAccount::RESERVED)->count());
        $this->assertSame(Payment::PENDING, $order->payment->status);

        // Browser mencoba memalsukan status: tidak ada pengaruh tanpa konfirmasi Casaku.
        $this->actingAs($user)->post("/pesanan/{$order->code}/cek-pembayaran", ['status' => 'paid']);
        $this->assertSame(Payment::PENDING, $order->payment->fresh()->status);

        app(CasakuGateway::class)->markPaid($order->payment->gateway_transaction_id);

        $svc = app(PaymentService::class);
        $this->assertSame(Payment::PAID, $svc->verify($order->payment, 'test'));
        $this->assertSame(Payment::PAID, $svc->verify($order->payment, 'test')); // idempoten

        $this->assertSame(2, Delivery::count());
        $this->assertSame(2, DigitalAccount::where('status', DigitalAccount::SOLD)->count());
        $this->assertSame(1, DigitalAccount::where('status', DigitalAccount::AVAILABLE)->count());
        $this->assertSame(Order::PAID, $order->fresh()->status);

        $this->actingAs($user)->post("/pesanan/{$order->code}/akun")
            ->assertOk()
            ->assertSee('@contoh.test')
            ->assertHeader('Cache-Control');
    }

    public function test_tagihan_kedaluwarsa_melepas_reservasi_dan_bayar_terlambat_masuk_review(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(stock: 1);

        $this->actingAs($user)->post("/checkout/{$product->slug}", ['quantity' => 1]);
        $payment = Payment::firstOrFail();

        app(CasakuGateway::class)->cancel($payment->gateway_transaction_id);
        $payment->update(['expires_at' => now()->subMinute()]);

        $this->assertSame(1, app(PaymentService::class)->expireOverdue());
        $this->assertSame(DigitalAccount::AVAILABLE, DigitalAccount::first()->status);
        $this->assertSame(Payment::EXPIRED, $payment->fresh()->status);

        app(CasakuGateway::class)->markPaid($payment->gateway_transaction_id);
        $this->assertSame(Payment::REVIEW, app(PaymentService::class)->verify($payment, 'test'));
        $this->assertSame(0, Delivery::count());
    }

    public function test_ciphertext_terikat_ke_produk_dan_menolak_perubahan(): void
    {
        $vault = app(CredentialVault::class);
        $c = $vault->encrypt('rahasia', CredentialVault::aad(1, 'password'));

        $this->assertSame('rahasia', $vault->decrypt($c, CredentialVault::aad(1, 'password')));
        $this->expectException(\RuntimeException::class);
        $vault->decrypt($c, CredentialVault::aad(2, 'password'));
    }

    public function test_webhook_tanpa_tanda_tangan_valid_ditolak(): void
    {
        config(['services.casaku.webhook_secret' => 'rahasia-webhook']);

        $this->postJson('/webhooks/casaku', ['transactionId' => 'X', 'status' => 'paid'], ['x-casaku-signature' => 'palsu'])
            ->assertStatus(401);
    }
}
