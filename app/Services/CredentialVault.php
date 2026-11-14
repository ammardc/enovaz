<?php

namespace App\Services;

use RuntimeException;

/**
 * Enkripsi terautentikasi untuk kredensial produk (XChaCha20-Poly1305 via libsodium).
 * Setiap nilai memakai nonce acak unik; tag autentikasi menolak ciphertext yang diubah.
 * AAD mengikat ciphertext ke produk dan kolomnya, sehingga ciphertext tidak bisa dipindah ke baris lain.
 */
class CredentialVault
{
    private const PREFIX = 'v1:';

    private ?string $key = null;

    public function __construct(private readonly ?string $encodedKey)
    {
    }

    public static function generateKey(): string
    {
        return base64_encode(sodium_crypto_aead_xchacha20poly1305_ietf_keygen());
    }

    public function encrypt(string $plaintext, string $aad): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, $this->key());

        return self::PREFIX.base64_encode($nonce.$cipher);
    }

    public function decrypt(string $payload, string $aad): string
    {
        if (! str_starts_with($payload, self::PREFIX)) {
            throw new RuntimeException('Format ciphertext tidak dikenal.');
        }

        $raw = base64_decode(substr($payload, strlen(self::PREFIX)), true);
        $nonceLen = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

        if ($raw === false || strlen($raw) <= $nonceLen + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            throw new RuntimeException('Ciphertext rusak.');
        }

        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(substr($raw, $nonceLen), $aad, substr($raw, 0, $nonceLen), $this->key());

        if ($plain === false) {
            throw new RuntimeException('Gagal mendekripsi: kunci salah atau data telah diubah.');
        }

        return $plain;
    }

    public static function aad(int $productId, string $field): string
    {
        return "enovaz:digital_account:{$productId}:{$field}";
    }

    private function key(): string
    {
        if ($this->key !== null) {
            return $this->key;
        }

        $key = base64_decode((string) $this->encodedKey, true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new RuntimeException('CREDENTIAL_KEY belum diisi atau tidak valid. Jalankan: php artisan enovaz:credential-key');
        }

        return $this->key = $key;
    }
}
