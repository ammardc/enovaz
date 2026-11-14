<?php

namespace Tests\Feature;

use App\Models\BalanceEntry;
use App\Models\Delivery;
use App\Models\DigitalAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\Casaku\CasakuGateway;
use App\Services\PaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BalanceTest extends TestCase
{
    use RefreshDatabase;

    private function giveBalance(User $user, int $amount): void
    {
        DB::transaction(fn () => app(BalanceService::class)->credit($user->id, $amount, BalanceEntry::ADJUSTMENT, null, 'uji'));
    }

    public function test_isi_saldo_hanya_masuk_setelah_casaku_lunas_dan_tidak_dobel(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/saldo/isi', ['amount' => 50000])->assertRedirect();
        $order = Order::where('kind', Order::KIND_TOPUP)->with('payment')->firstOrFail();
        $this->assertSame(0, $user->fresh()->balance);

        // Browser memalsukan status: diabaikan.
        $this->actingAs($user)->post("/pesanan/{$order->code}/cek-pembayaran", ['status' => 'paid']);
        $this->assertSame(0, $user->fresh()->balance);

        app(CasakuGateway::class)->markPaid($order->payment->gateway_transaction_id);
        $svc = app(PaymentService::class);
        $this->assertSame(Payment::PAID, $svc->verify($order->payment, 'test'));
        $this->assertSame(Payment::PAID, $svc->verify($order->payment, 'test')); // dicek ulang

        $paid = $order->payment->fresh()->expected_amount;
        $this->assertSame($paid, $user->fresh()->balance);
        $this->assertSame(1, BalanceEntry::where('order_id', $order->id)->count());
    }

    public function test_bayar_terlambat_untuk_isi_saldo_tetap_dikreditkan(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post('/saldo/isi', ['amount' => 20000]);
        $payment = Payment::firstOrFail();

        app(CasakuGateway::class)->cancel($payment->gateway_transaction_id);
        $payment->update(['expires_at' => now()->subMinute()]);
        app(PaymentService::class)->expireOverdue();
        $this->assertSame(Payment::EXPIRED, $payment->fresh()->status);

        app(CasakuGateway::class)->markPaid($payment->gateway_transaction_id);
        $this->assertSame(Payment::PAID, app(PaymentService::class)->verify($payment, 'test'));
        $this->assertSame($payment->fresh()->expected_amount, $user->fresh()->balance);
    }

    public function test_beli_pakai_saldo_memotong_saldo_dan_menyerahkan_akun(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(stock: 3, price: 25000);
        $this->giveBalance($user, 60000);

        $this->actingAs($user)->post("/checkout/{$product->slug}", ['quantity' => 2, 'method' => 'balance'])->assertRedirect();

        $order = Order::where('kind', Order::KIND_PURCHASE)->firstOrFail();
        $this->assertSame(Order::PAID, $order->status);
        $this->assertSame(Order::METHOD_BALANCE, $order->payment_method);
        $this->assertNull($order->payment); // tidak ada tagihan QRIS
        $this->assertSame(10000, $user->fresh()->balance);
        $this->assertSame(2, Delivery::count());
        $this->assertSame(-50000, BalanceEntry::where('order_id', $order->id)->value('amount'));

        $this->actingAs($user)->post("/pesanan/{$order->code}/akun")->assertOk()->assertSee('@contoh.test');
    }

    public function test_saldo_kurang_tidak_mengubah_apa_pun(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(stock: 2, price: 25000);
        $this->giveBalance($user, 10000);

        $this->actingAs($user)->post("/checkout/{$product->slug}", ['quantity' => 1, 'method' => 'balance'])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(10000, $user->fresh()->balance);
        $this->assertSame(0, Order::count());
        $this->assertSame(2, DigitalAccount::where('status', DigitalAccount::AVAILABLE)->count());
        $this->assertSame(1, BalanceEntry::count()); // hanya saldo awal
    }

    public function test_harga_dan_saldo_dari_browser_diabaikan(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(stock: 1, price: 25000);
        $this->giveBalance($user, 30000);

        $this->actingAs($user)->post("/checkout/{$product->slug}", [
            'quantity' => 1, 'method' => 'balance', 'price' => 1, 'total' => 1, 'amount' => 1,
        ]);

        $this->assertSame(5000, $user->fresh()->balance); // dipotong harga asli dari DB

        // Mass assignment saldo lewat pendaftaran ditolak.
        $this->post('/keluar');
        $this->post('/daftar', [
            'captcha' => $this->solveCaptcha(),
            'name' => 'Curang', 'username' => 'curang', 'password' => 'RahasiaPanjang123',
            'password_confirmation' => 'RahasiaPanjang123', 'balance' => 999999,
        ]);
        $this->assertSame(0, User::where('username', 'curang')->first()->balance);
    }

    public function test_metode_bayar_dan_nominal_isi_saldo_divalidasi(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($user)->post("/checkout/{$product->slug}", ['quantity' => 1, 'method' => 'gratis'])
            ->assertSessionHasErrors('method');
        $this->actingAs($user)->post('/saldo/isi', ['amount' => -50000])->assertSessionHasErrors('amount');
        $this->actingAs($user)->post('/saldo/isi', ['amount' => 999999999])->assertSessionHasErrors('amount');
        $this->assertSame(0, Order::count());
    }

    public function test_kredit_ganda_untuk_pesanan_yang_sama_ditolak_database(): void
    {
        $user = $this->makeUser();
        $order = Order::create(['user_id' => $user->id, 'code' => 'TOP-UJI', 'kind' => 'topup', 'total' => 10000, 'status' => 'paid']);
        $svc = app(BalanceService::class);

        DB::transaction(fn () => $svc->credit($user->id, 10000, BalanceEntry::TOPUP, $order));

        try {
            DB::transaction(fn () => $svc->credit($user->id, 10000, BalanceEntry::TOPUP, $order));
            $this->fail('Kredit kedua seharusnya ditolak.');
        } catch (QueryException) {
            // UNIQUE(order_id, type)
        }
        $this->assertSame(10000, $user->fresh()->balance);
    }

    public function test_riwayat_saldo_tidak_bisa_diubah_dan_tidak_bocor_ke_pengguna_lain(): void
    {
        $a = $this->makeUser('pembeli_a');
        $b = $this->makeUser('pembeli_b');
        $this->giveBalance($a, 77000);

        $entry = BalanceEntry::firstOrFail();
        try {
            $entry->update(['amount' => 1]);
            $this->fail('Ledger seharusnya tidak bisa diubah.');
        } catch (\LogicException) {
        }
        $this->assertSame(77000, $entry->fresh()->amount);

        $this->actingAs($b)->get('/saldo')->assertOk()->assertDontSee('77.000');
        $this->flushSession(); // pembeli A = browser lain
        $this->actingAs($a)->get('/saldo')->assertOk()->assertSee('77.000');
    }
}
