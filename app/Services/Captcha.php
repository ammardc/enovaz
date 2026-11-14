<?php

namespace App\Services;

use Illuminate\Contracts\Session\Session;
use RuntimeException;

/**
 * CAPTCHA gambar buatan sendiri (tanpa layanan pihak ketiga) untuk menahan bot di login & daftar.
 *
 * - Gambar PNG raster (bukan SVG/teks), jadi jawabannya tidak bisa dibaca dari source halaman.
 * - Jawaban tidak disimpan apa adanya: hanya HMAC-nya di sesi, dengan batas waktu.
 * - Sekali pakai: setiap pemeriksaan (benar atau salah) menghapus tantangan, jadi tidak bisa diulang.
 */
class Captcha
{
    private const KEY = 'captcha';
    private const TTL = 120; // detik
    private const LENGTH = 5;
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa 0/O, 1/I yang membingungkan

    /** Hanya untuk pengujian otomatis: paksa kode tertentu. */
    private static ?string $fake = null;

    public function __construct(private readonly Session $session)
    {
    }

    public static function fake(?string $code): void
    {
        self::$fake = $code;
    }

    /** Buat tantangan baru dan kembalikan gambar PNG-nya. */
    public function issue(): string
    {
        $code = self::$fake ?? $this->randomCode();
        $this->session->put(self::KEY, [
            'hash' => $this->hash($code),
            'expires' => now()->getTimestamp() + self::TTL,
        ]);

        return $this->render($code);
    }

    /** Periksa jawaban. Tantangan selalu dihapus sesudahnya (sekali pakai). */
    public function check(?string $answer): bool
    {
        $challenge = $this->session->pull(self::KEY);

        if (! is_array($challenge) || ($challenge['expires'] ?? 0) < now()->getTimestamp() || ! is_string($answer) || $answer === '') {
            return false;
        }

        return hash_equals($challenge['hash'], $this->hash($answer));
    }

    private function hash(string $code): string
    {
        $normal = strtoupper(preg_replace('/\s+/', '', $code));

        return hash_hmac('sha256', $normal, (string) config('app.key'));
    }

    private function randomCode(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }

        return $code;
    }

    private function render(string $code): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('Ekstensi PHP GD belum aktif (php.ini: extension=gd).');
        }

        $w = 200;
        $h = 64;
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 246, 244, 251));

        // Bintik acak
        for ($i = 0; $i < 450; $i++) {
            $c = imagecolorallocate($img, random_int(150, 215), random_int(140, 205), random_int(170, 230));
            imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $c);
        }

        // Garis pengganggu di belakang huruf
        for ($i = 0; $i < 5; $i++) {
            $c = imagecolorallocate($img, random_int(170, 210), random_int(160, 200), random_int(190, 230));
            imagesetthickness($img, random_int(1, 2));
            imageline($img, random_int(0, $w / 3), random_int(0, $h), random_int($w * 2 / 3, $w), random_int(0, $h), $c);
        }

        $font = resource_path('fonts/DejaVuSans-Bold.ttf');
        $x = 14;
        foreach (str_split($code) as $ch) {
            $c = imagecolorallocate($img, random_int(20, 90), random_int(10, 50), random_int(80, 160));
            $size = random_int(22, 27);
            $angle = random_int(-25, 25);
            $y = random_int(38, 50);
            if (is_file($font) && function_exists('imagettftext')) {
                imagettftext($img, $size, $angle, $x, $y, $c, $font, $ch);
            } else {
                imagestring($img, 5, $x, $y - 20, $ch, $c);
            }
            $x += random_int(31, 36);
        }

        // Dua garis lengkung di atas huruf supaya OCR sederhana gagal
        imagesetthickness($img, 2);
        for ($k = 0; $k < 2; $k++) {
            $c = imagecolorallocate($img, random_int(60, 120), random_int(40, 90), random_int(120, 180));
            $amp = random_int(6, 12);
            $phase = random_int(0, 628) / 100;
            $base = random_int(22, 42);
            $prev = null;
            for ($x2 = 0; $x2 < $w; $x2 += 4) {
                $y2 = (int) ($base + $amp * sin($x2 / 22 + $phase));
                if ($prev) {
                    imageline($img, $prev[0], $prev[1], $x2, $y2, $c);
                }
                $prev = [$x2, $y2];
            }
        }

        ob_start();
        imagepng($img);
        imagedestroy($img);

        return (string) ob_get_clean();
    }
}
