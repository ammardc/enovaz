<?php

return [
    // Kunci enkripsi kredensial produk (32 byte, base64). Terpisah dari APP_KEY dan database.
    // Buat dengan: php artisan enovaz:credential-key
    'credential_key' => env('CREDENTIAL_KEY'),

    // Batas waktu autentikasi ulang untuk tindakan admin sensitif (detik).
    'reauth_seconds' => (int) env('REAUTH_SECONDS', 600),

    // Mencegah penimbunan stok lewat banyak tagihan menggantung.
    'max_pending_orders' => (int) env('MAX_PENDING_ORDERS', 3),

    'max_quantity' => 5,

    // Panjang minimal password (NIST SP 800-63B: akun tanpa 2FA sebaiknya lebih panjang)
    'password_min' => ['pembeli' => 12, 'admin' => 16],

    // Stok dianggap menipis (chip "Sisa N", warna oranye, daftar stok menipis di admin)
    'low_stock' => 5,

    // QRIS (Casaku) bisa dimatikan: false = maintenance, pembelian hanya lewat saldo (saldo diisi admin).
    'qris_enabled' => filter_var(env('QRIS_ENABLED', false), FILTER_VALIDATE_BOOL),

    // Batas penambahan saldo oleh admin per transaksi (rupiah)
    'admin_credit_max' => 10000000,

    // Isi saldo lewat QRIS (rupiah)
    'topup' => [
        'min' => 10000,
        'max' => 2000000,
        'presets' => [25000, 50000, 100000, 200000],
    ],

    'support' => [
        'email' => env('SUPPORT_EMAIL', 'support@enovaz.id'),
        'telegram' => env('SUPPORT_TELEGRAM'),
        'hours' => '08.00–22.00 WIB',
        'city' => 'Cilacap, Jawa Tengah',
    ],
];
