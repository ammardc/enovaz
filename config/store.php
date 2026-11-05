<?php

return [
    'payments_enabled' => env('PAYMENTS_ENABLED', false),
    'casaku_live_verified' => env('CASAKU_LIVE_VERIFIED', false),
    'casaku_key' => env('CASAKU_LICENSE_KEY'),
    'casaku_secret' => env('CASAKU_WEBHOOK_SECRET'),
    'casaku_qr_id' => env('CASAKU_QR_ID'),
    'casaku_package_ids' => array_filter(explode(',', env('CASAKU_PACKAGE_IDS', 'id.dana'))),
    'casaku_base_url' => env('CASAKU_BASE_URL', 'https://api.casaku.id'),
    'inventory_key' => env('INVENTORY_KEY'),
    'telegram_url' => env('TELEGRAM_URL'),
    'topup_min' => (int) env('TOPUP_MIN_IDR', 10000),
    'topup_max' => (int) env('TOPUP_MAX_IDR', 2000000),
];
