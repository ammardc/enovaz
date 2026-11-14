<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Captcha;

class CaptchaController extends Controller
{
    /** Gambar CAPTCHA baru; setiap permintaan mengganti tantangan lama di sesi. */
    public function show(Captcha $captcha)
    {
        return response($captcha->issue(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, private, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
