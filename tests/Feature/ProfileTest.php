<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_hanya_untuk_yang_login(): void
    {
        $this->get('/profil')->assertRedirect('/masuk');
    }

    public function test_ubah_nama_tidak_bisa_mengubah_role_username_atau_saldo(): void
    {
        $user = $this->makeUser('budi');

        $this->actingAs($user)->put('/profil/nama', [
            'name' => 'Budi Baru', 'username' => 'admin', 'role' => 'admin', 'balance' => 999999,
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('Budi Baru', $user->name);
        $this->assertSame('budi', $user->username);
        $this->assertSame(User::ROLE_PEMBELI, $user->role);
        $this->assertSame(0, $user->balance);
    }

    public function test_ganti_password_wajib_password_lama_yang_benar(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => 'salah123',
            'password' => 'BaruPanjang12345', 'password_confirmation' => 'BaruPanjang12345',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Rahasia123', $user->fresh()->password_hash));
        $this->assertTrue(AuditLog::where('action', 'profile.password_change_failed')->exists());
    }

    public function test_ganti_password_berhasil_tetap_argon2id_dan_tercatat(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => 'Rahasia123',
            'password' => 'BaruPanjang12345', 'password_confirmation' => 'BaruPanjang12345',
        ])->assertSessionHasNoErrors();

        $hash = $user->fresh()->getRawOriginal('password_hash');
        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertTrue(Hash::check('BaruPanjang12345', $hash));
        $this->assertTrue(AuditLog::where('action', 'profile.password_changed')->exists());

        // Sesi ini tetap login setelah ganti password.
        $this->get('/profil')->assertOk();
    }

    public function test_sesi_di_perangkat_lain_keluar_setelah_password_diganti(): void
    {
        $user = $this->makeUser();

        // "Perangkat lain": sesi ini sudah login dengan hash password lama.
        $this->actingAs($user)->get('/profil')->assertOk();

        // Password diganti dari perangkat lain.
        $user->password_hash = 'BaruPanjang12345';
        $user->save();

        $this->get('/profil')->assertRedirect('/masuk');
        $this->assertGuest();
    }

    public function test_panjang_minimal_pembeli_12_dan_admin_16(): void
    {
        $buyer = $this->makeUser('budi');
        $this->actingAs($buyer)->put('/profil/password', ['current_password' => 'Rahasia123', 'password' => 'Abcdefgh123', 'password_confirmation' => 'Abcdefgh123'])
            ->assertSessionHasErrors('password'); // 11 karakter
        $this->actingAs($buyer)->put('/profil/password', ['current_password' => 'Rahasia123', 'password' => 'Abcdefgh1234', 'password_confirmation' => 'Abcdefgh1234'])
            ->assertSessionHasNoErrors();         // 12 karakter

        $this->flushSession();
        $admin = $this->makeUser('admin1', User::ROLE_ADMIN);
        app(\App\Services\Totp::class)->enable($admin, 'JBSWY3DPEHPK3PXP', 0);
        $this->actingAs($admin->fresh())->put('/profil/password', ['current_password' => 'Rahasia123', 'password' => 'Abcdefghijkl123', 'password_confirmation' => 'Abcdefghijkl123'])
            ->assertSessionHasErrors('password'); // 15 karakter
        $this->actingAs($admin->fresh())->put('/profil/password', ['current_password' => 'Rahasia123', 'password' => 'Abcdefghijklm123', 'password_confirmation' => 'Abcdefghijklm123'])
            ->assertSessionHasNoErrors();         // 16 karakter

        $this->flushSession();
        $this->app['auth']->forgetGuards(); // kembali jadi tamu
        $this->post('/daftar', ['captcha' => $this->solveCaptcha(), 'name' => 'Pendek', 'username' => 'pendek', 'password' => 'Abcdefgh123', 'password_confirmation' => 'Abcdefgh123'])
            ->assertSessionHasErrors('password');
    }

    public function test_password_baru_harus_berbeda_dan_cukup_kuat(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => 'Rahasia123', 'password' => 'Rahasia123', 'password_confirmation' => 'Rahasia123',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => 'Rahasia123', 'password' => '123', 'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }
}
