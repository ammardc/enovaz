<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BalanceEntry;
use App\Models\Order;
use App\Models\User;
use App\Services\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** QRIS maintenance + admin menambah saldo pembeli. */
class SaldoAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $a = $this->makeUser('admin1', User::ROLE_ADMIN);
        app(Totp::class)->enable($a, 'JBSWY3DPEHPK3PXP', 0);

        return $a->fresh();
    }

    public function test_qris_maintenance_ditolak_di_server_walau_form_dimanipulasi(): void
    {
        config(['enovaz.qris_enabled' => false]);
        $buyer = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($buyer)->post("/checkout/{$product->slug}", ['quantity' => 1, 'method' => 'qris'])
            ->assertSessionHasErrors('quantity');
        $this->actingAs($buyer)->post('/saldo/isi', ['amount' => 50000])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Order::count());
        $this->actingAs($buyer)->get("/produk/{$product->slug}")->assertSee('Maintenance');
    }

    public function test_admin_menambah_saldo_pembeli_dengan_konfirmasi_password_dan_tercatat(): void
    {
        $admin = $this->admin();
        $buyer = $this->makeUser('budi');

        // Tanpa konfirmasi ulang password: dialihkan.
        $this->actingAs($admin)->post("/admin/pengguna/{$buyer->id}/saldo", ['amount' => 50000, 'note' => 'uji'])
            ->assertRedirect('/konfirmasi-password');
        $this->assertSame(0, $buyer->fresh()->balance);

        $this->withSession(['auth.reauth_at' => time()])->actingAs($admin)
            ->post("/admin/pengguna/{$buyer->id}/saldo", ['amount' => 50000, 'note' => 'saldo pentest'])
            ->assertSessionHasNoErrors();

        $this->assertSame(50000, $buyer->fresh()->balance);
        $this->assertSame(50000, BalanceEntry::where('user_id', $buyer->id)->value('amount'));
        $this->assertTrue(AuditLog::where('action', 'admin.balance_credited')->where('actor_user_id', $admin->id)->exists());

        // Pembeli bisa langsung belanja pakai saldo itu.
        $product = $this->makeProduct(stock: 1, price: 25000);
        $this->flushSession();
        $this->actingAs($buyer)->post("/checkout/{$product->slug}", ['quantity' => 1, 'method' => 'balance'])->assertRedirect();
        $this->assertSame(25000, $buyer->fresh()->balance);
    }

    public function test_nominal_tidak_valid_dan_target_admin_ditolak(): void
    {
        $admin = $this->admin();
        $buyer = $this->makeUser('budi');
        $other = $this->makeUser('admin2', User::ROLE_ADMIN);
        $s = ['auth.reauth_at' => time()];

        foreach ([-5000, 0, 500, 999999999, 'abc'] as $bad) {
            $this->withSession($s)->actingAs($admin)->post("/admin/pengguna/{$buyer->id}/saldo", ['amount' => $bad, 'note' => 'x y'])
                ->assertSessionHasErrors('amount');
        }
        $this->withSession($s)->actingAs($admin)->post("/admin/pengguna/{$other->id}/saldo", ['amount' => 5000, 'note' => 'uji']);

        $this->assertSame(0, $buyer->fresh()->balance);
        $this->assertSame(0, $other->fresh()->balance);
        $this->assertSame(0, BalanceEntry::count());
    }

    public function test_pembeli_tidak_bisa_menambah_saldo_sendiri(): void
    {
        $buyer = $this->makeUser('budi');

        $this->withSession(['auth.reauth_at' => time()])->actingAs($buyer)
            ->post("/admin/pengguna/{$buyer->id}/saldo", ['amount' => 50000, 'note' => 'curang'])
            ->assertForbidden();
        $this->assertSame(0, $buyer->fresh()->balance);
    }
}
