<?php

namespace App\Services;

class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function secret(): string
    {
        $bytes = random_bytes(20); $bits = '';
        foreach (str_split($bytes) as $byte) $bits .= str_pad(decbin(ord($byte)),8,'0',STR_PAD_LEFT);
        $result = '';
        foreach (str_split($bits,5) as $chunk) $result .= self::ALPHABET[bindec($chunk)];
        return $result;
    }

    public function verify(string $secret, string $code, ?int $lastStep = null): ?int
    {
        if (! preg_match('/^\d{6}$/',$code)) return null;
        $step = intdiv(time(),30);
        foreach ([$step-1,$step,$step+1] as $candidate) {
            if ($lastStep !== null && $candidate <= $lastStep) continue;
            if (hash_equals($this->code($secret,$candidate),$code)) return $candidate;
        }
        return null;
    }

    private function code(string $secret, int $step): string
    {
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $position = strpos(self::ALPHABET,$char);
            if ($position === false) return '';
            $bits .= str_pad(decbin($position),5,'0',STR_PAD_LEFT);
        }
        $key = '';
        foreach (str_split($bits,8) as $octet) if (strlen($octet) === 8) $key .= chr(bindec($octet));
        $hash = hash_hmac('sha1',pack('N2',0,$step),$key,true);
        $offset = ord($hash[19]) & 15;
        $number = ((ord($hash[$offset]) & 127) << 24) | (ord($hash[$offset+1]) << 16) | (ord($hash[$offset+2]) << 8) | ord($hash[$offset+3]);
        return str_pad((string) ($number % 1000000),6,'0',STR_PAD_LEFT);
    }
}
