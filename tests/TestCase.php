<?php

namespace Tests;

use App\Models\DigitalAccount;
use App\Models\Product;
use App\Models\User;
use App\Services\Captcha;
use App\Services\CredentialVault;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        Captcha::fake(null);
        parent::tearDown();
    }

    /** Terbitkan CAPTCHA dengan kode yang diketahui (lewat endpoint asli) dan kembalikan kodenya. */
    protected function solveCaptcha(string $code = 'TEST5'): string
    {
        Captcha::fake($code);
        $this->get('/captcha.png')->assertOk()->assertHeader('Content-Type', 'image/png');

        return $code;
    }

    protected function makeUser(string $username = 'pembeli1', string $role = User::ROLE_PEMBELI): User
    {
        $u = new User;
        $u->name = ucfirst($username);
        $u->username = $username;
        $u->password_hash = 'Rahasia123';
        $u->role = $role;
        $u->save();

        return $u;
    }

    protected function makeProduct(int $stock = 3, int $price = 25000): Product
    {
        $product = Product::create([
            'name' => 'Produk Uji '.uniqid(),
            'slug' => 'produk-uji-'.uniqid(),
            'category' => 'Uji',
            'description' => 'Deskripsi',
            'price' => $price,
            'duration' => '1 bulan',
            'is_active' => true,
        ]);

        $vault = app(CredentialVault::class);
        for ($i = 1; $i <= $stock; $i++) {
            DigitalAccount::create([
                'product_id' => $product->id,
                'email_enc' => $vault->encrypt("akun{$i}@contoh.test", CredentialVault::aad($product->id, 'email')),
                'password_enc' => $vault->encrypt("pass{$i}", CredentialVault::aad($product->id, 'password')),
                'status' => DigitalAccount::AVAILABLE,
            ]);
        }

        return $product;
    }
}
