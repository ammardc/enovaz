<?php

namespace Tests\Feature;

use App\Models\BalanceEntry;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    /** Admin dengan 2FA aktif (panel admin mewajibkannya). */
    private function makeAdmin(): User
    {
        $admin = $this->makeUser('admin1', User::ROLE_ADMIN);
        app(\App\Services\Totp::class)->enable($admin, 'JBSWY3DPEHPK3PXP', 0);

        return $admin->fresh();
    }

    public function test_dashboard_menghitung_penjualan_tanpa_isi_saldo(): void
    {
        $admin = $this->makeAdmin();
        $buyer = $this->makeUser('pembeli1');
        $product = $this->makeProduct(stock: 5, price: 25000);

        // Isi saldo 100rb (bukan penjualan), lalu beli 2 produk pakai saldo (penjualan 50rb).
        DB::transaction(fn () => app(BalanceService::class)->credit($buyer->id, 100000, BalanceEntry::ADJUSTMENT));
        $this->actingAs($buyer)->post("/checkout/{$product->slug}", ['quantity' => 2, 'method' => 'balance']);

        $this->flushSession();
        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Penjualan 14 hari terakhir')
            ->assertSee('Rp50.000')          // penjualan hari ini & total 14 hari
            ->assertSee('Rp50.000 · 1 pesanan', false) // tooltip hari ini
            ->assertSee($product->name)       // terlaris
            ->assertDontSee('Rp150.000');     // isi saldo tidak ikut dijumlah ke penjualan
    }

    public function test_dashboard_kosong_tetap_tampil(): void
    {
        $this->actingAs($this->makeAdmin())->get('/admin')
            ->assertOk()
            ->assertSee('Belum ada penjualan.')
            ->assertSee('Belum ada pesanan.');
    }

    public function test_pembeli_tidak_bisa_membuka_dashboard(): void
    {
        $this->actingAs($this->makeUser())->get('/admin')->assertForbidden();
    }
}
