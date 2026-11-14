<?php

return [
    'casaku' => [
        // fake  = simulasi lokal tanpa memanggil Casaku (ditolak di production)
        // live  = memanggil API Casaku sungguhan
        'driver' => env('CASAKU_DRIVER', 'fake'),
        'base_url' => env('CASAKU_BASE_URL', 'https://api.casaku.id'),
        'license_key' => env('CASAKU_LICENSE_KEY'),
        'qr_id' => env('CASAKU_QR_ID'),
        'package_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('CASAKU_PACKAGE_IDS', 'id.dana'))))),
        'webhook_secret' => env('CASAKU_WEBHOOK_SECRET'),
        'expires_minutes' => (int) env('CASAKU_EXPIRES_MINUTES', 15),
        'prefix' => env('CASAKU_PREFIX', 'ENV'),
    ],
];
