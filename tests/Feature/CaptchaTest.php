<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_gambar_captcha_png_tanpa_cache_dan_tanpa_jawaban_di_respons(): void
    {
        Captcha::fake('ABCDE');
        $res = $this->get('/captcha.png')->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertStringStartsWith("\x89PNG", $res->getContent());
        $this->assertStringNotContainsString('ABCDE', $res->getContent());   // jawaban tidak ikut terkirim
        $this->assertStringNotContainsString('ABCDE', json_encode(session()->all())); // sesi hanya menyimpan hash
    }

    public function test_login_tanpa_captcha_atau_captcha_salah_ditolak_walau_password_benar(): void
    {
        $this->makeUser('budi');

        $this->post('/masuk', ['username' => 'budi', 'password' => 'Rahasia123'])->assertSessionHasErrors('captcha');
        $this->assertGuest();

        $this->solveCaptcha('ABCDE');
        $this->post('/masuk', ['username' => 'budi', 'password' => 'Rahasia123', 'captcha' => 'ZZZZZ'])->assertSessionHasErrors('captcha');
        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'auth.captcha_failed')->exists());
    }

    public function test_login_dengan_captcha_benar_berhasil_dan_tidak_peka_huruf_besar(): void
    {
        $this->makeUser('budi');
        $this->solveCaptcha('ABCDE');

        $this->post('/masuk', ['username' => 'budi', 'password' => 'Rahasia123', 'captcha' => 'abcde'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_captcha_sekali_pakai_tidak_bisa_diulang(): void
    {
        $this->makeUser('budi');
        $code = $this->solveCaptcha('ABCDE');

        // Percobaan pertama dengan password salah memakai captcha itu.
        $this->post('/masuk', ['username' => 'budi', 'password' => 'salah', 'captcha' => $code])->assertSessionHasErrors('username');

        // Mengulang kode yang sama (replay) ditolak, walau password benar.
        $this->post('/masuk', ['username' => 'budi', 'password' => 'Rahasia123', 'captcha' => $code])->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_captcha_kedaluwarsa_ditolak(): void
    {
        $this->makeUser('budi');
        $code = $this->solveCaptcha('ABCDE');
        $this->travel(3)->minutes();

        $this->post('/masuk', ['username' => 'budi', 'password' => 'Rahasia123', 'captcha' => $code])->assertSessionHasErrors('captcha');
        $this->assertGuest();
    }

    public function test_daftar_mengecek_captcha_sebelum_membocorkan_username_terpakai(): void
    {
        $this->makeUser('sudahada');

        $res = $this->post('/daftar', [
            'name' => 'X', 'username' => 'sudahada', 'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123',
        ]);

        $res->assertSessionHasErrors('captcha');
        $res->assertSessionDoesntHaveErrors('username'); // tidak ada petunjuk bahwa username sudah dipakai
        $this->assertSame(1, User::count());
    }
}
