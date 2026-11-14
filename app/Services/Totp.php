<?php

namespace App\Services;

use App\Models\User;

/**
 * TOTP (RFC 6238, HMAC-SHA1, 6 digit, 30 detik) tanpa library atau layanan pihak ketiga.
 * Cocok dengan aplikasi authenticator standar (Google/Microsoft Authenticator, Aegis, dll).
 *
 * Rahasia per user disimpan terenkripsi (CredentialVault, AAD terikat ke user).
 * Kode yang sudah dipakai tidak bisa dipakai lagi (users.totp_last_step).
 */
class Totp
{
    public const PERIOD = 30;
    public const DIGITS = 6;
    private const WINDOW = 1; // toleransi jam HP ±30 detik
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct(private readonly CredentialVault $vault)
    {
    }

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20)); // 160 bit
    }

    public function uri(string $secret, string $account): string
    {
        $issuer = (string) config('app.name', 'E-Novaz');

        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account)
            .'?secret='.$secret.'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1&digits='.self::DIGITS.'&period='.self::PERIOD;
    }

    public function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function currentStep(): int
    {
        return intdiv(now()->getTimestamp(), self::PERIOD);
    }

    /** Langkah waktu yang cocok, atau null. $after = langkah terakhir yang sudah dipakai (cegah replay). */
    public function match(string $secret, ?string $input, ?int $after = null): ?int
    {
        $input = preg_replace('/\D/', '', (string) $input);
        if (strlen($input) !== self::DIGITS) {
            return null;
        }

        $now = $this->currentStep();
        for ($d = -self::WINDOW; $d <= self::WINDOW; $d++) {
            $step = $now + $d;
            if ($after !== null && $step <= $after) {
                continue;
            }
            if (hash_equals($this->code($secret, $step), $input)) {
                return $step;
            }
        }

        return null;
    }

    /** Verifikasi kode untuk user yang 2FA-nya aktif; catat langkahnya supaya kode itu tidak bisa diulang. */
    public function verifyFor(User $user, ?string $input): bool
    {
        if (! $user->hasTwoFactor()) {
            return false;
        }

        $step = $this->match($this->secretOf($user), $input, $user->totp_last_step);
        if ($step === null) {
            return false;
        }

        $user->forceFill(['totp_last_step' => $step])->save();

        return true;
    }

    public function enable(User $user, string $secret, int $step): void
    {
        $user->forceFill([
            'totp_secret_enc' => $this->vault->encrypt($secret, self::aad($user->id)),
            'totp_enabled_at' => now(),
            'totp_last_step' => $step,
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill(['totp_secret_enc' => null, 'totp_enabled_at' => null, 'totp_last_step' => null])->save();
    }

    public function secretOf(User $user): string
    {
        return $this->vault->decrypt((string) $user->totp_secret_enc, self::aad($user->id));
    }

    public static function aad(int $userId): string
    {
        return 'enovaz:user:'.$userId.':totp';
    }

    private function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $b) {
            $bits .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret));
        $bits = '';
        foreach (str_split($secret) as $c) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
