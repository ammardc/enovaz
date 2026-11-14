<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    private function adminWith2fa(string $username = 'admin1'): User
    {
        $admin = $this->makeUser($username, User::ROLE_ADMIN);
        app(Totp::class)->enable($admin, self::SECRET, 0);

        return $admin->fresh();
    }

    private function codeNow(int $offsetSteps = 0): string
    {
        $t = app(Totp::class);

        return $t->code(self::SECRET, $t->currentStep() + $offsetSteps);
    }

    private function loginPassword(string $username): \Illuminate\Testing\TestResponse
    {
        return $this->post('/masuk', ['username' => $username, 'password' => 'Rahasia123', 'captcha' => $this->solveCaptcha()]);
    }

    public function test_password_benar_saja_belum_login_untuk_admin_ber2fa(): void
    {
        $this->adminWith2fa();

        $this->loginPassword('admin1')->assertRedirect('/masuk/verifikasi');
        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/masuk');
    }

    public function test_kode_benar_masuk_kode_salah_ditolak(): void
    {
        $this->adminWith2fa();
        $this->loginPassword('admin1');

        $this->post('/masuk/verifikasi', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/masuk/verifikasi', ['code' => $this->codeNow()])->assertRedirect('/admin');
        $this->assertAuthenticated();
        $this->assertTrue(AuditLog::where('action', 'auth.2fa_failed')->exists());
    }

    public function test_kode_yang_sama_tidak_bisa_dipakai_ulang(): void
    {
        $this->adminWith2fa();
        $code = $this->codeNow();

        $this->loginPassword('admin1');
        $this->post('/masuk/verifikasi', ['code' => $code])->assertRedirect('/admin');

        $this->post('/keluar');
        $this->flushSession();
        $this->loginPassword('admin1');
        $this->post('/masuk/verifikasi', ['code' => $code])->assertSessionHasErrors('code'); // replay
        $this->assertGuest();
    }

    public function test_lima_kode_salah_membatalkan_dan_wajib_mulai_dari_awal(): void
    {
        $this->adminWith2fa();
        $this->loginPassword('admin1');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/masuk/verifikasi', ['code' => '11111'.$i])->assertSessionHasErrors('code');
        }
        $this->post('/masuk/verifikasi', ['code' => '999999'])->assertRedirect('/masuk');

        // Kode benar pun tidak berlaku lagi tanpa ulang password + CAPTCHA.
        $this->post('/masuk/verifikasi', ['code' => $this->codeNow()])->assertRedirect('/masuk');
        $this->assertGuest();
    }

    public function test_halaman_kode_tanpa_lolos_password_dialihkan(): void
    {
        $this->get('/masuk/verifikasi')->assertRedirect('/masuk');
        $this->post('/masuk/verifikasi', ['code' => '123456'])->assertRedirect('/masuk');
    }

    public function test_admin_tanpa_2fa_dipaksa_menyetel_dan_rahasia_disimpan_terenkripsi(): void
    {
        $admin = $this->makeUser('admin1', User::ROLE_ADMIN);

        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/2fa');
        $this->actingAs($admin)->get('/admin/produk')->assertRedirect('/admin/2fa');

        // Setel 2FA butuh konfirmasi ulang password.
        $this->actingAs($admin)->get('/admin/2fa')->assertRedirect('/konfirmasi-password');
        $this->withSession(['auth.reauth_at' => time()])->actingAs($admin)->get('/admin/2fa')->assertOk()->assertSee('Scan QR');

        $secret = session('admin.2fa_setup_secret');
        $t = app(Totp::class);
        $this->post('/admin/2fa', ['code' => $t->code($secret, $t->currentStep())])->assertRedirect('/admin');

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactor());
        $this->assertStringNotContainsString($secret, (string) $admin->getRawOriginal('totp_secret_enc'));
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_pembeli_tidak_terpengaruh_2fa(): void
    {
        $this->makeUser('budi');
        $this->loginPassword('budi')->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_reset_lewat_artisan(): void
    {
        $admin = $this->adminWith2fa();
        $this->artisan('enovaz:2fa-reset', ['username' => 'admin1'])->assertSuccessful();
        $this->assertFalse($admin->fresh()->hasTwoFactor());
    }
}
