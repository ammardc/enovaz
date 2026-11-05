<?php

namespace App\Services;

use RuntimeException;

class InventoryCipher
{
    private function key(): string
    {
        $key = base64_decode((string) config('store.inventory_key'), true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('INVENTORY_KEY harus berisi 32 byte base64.');
        }
        return $key;
    }

    public function encrypt(array $unit): string
    {
        $nonce = random_bytes(12);
        $plaintext = json_encode($unit, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false) throw new RuntimeException('Enkripsi stok gagal.');
        return base64_encode($nonce.$tag.$ciphertext);
    }

    public function decrypt(string $encoded): array
    {
        $blob = base64_decode($encoded, true);
        if ($blob === false || strlen($blob) < 29) throw new RuntimeException('Data stok rusak.');
        $plaintext = openssl_decrypt(substr($blob, 28), 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, substr($blob, 0, 12), substr($blob, 12, 16));
        if ($plaintext === false) throw new RuntimeException('Dekripsi stok gagal.');
        return json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
    }

    public function fingerprint(array $unit): string
    {
        $identity = $unit['code'] ? 'code:'.strtolower(trim($unit['code'])) : 'account:'.strtolower(trim($unit['account']));
        return hash_hmac('sha256', $identity, $this->key());
    }
}
