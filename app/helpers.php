<?php

use Carbon\CarbonInterface;

if (! function_exists('rupiah')) {
    function rupiah(int|float|string|null $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}

if (! function_exists('rupiah_short')) {
    /** Rupiah ringkas untuk sumbu grafik dan angka besar: 0, 50rb, 1,2jt. */
    function rupiah_short(int|float $amount): string
    {
        $a = abs($amount);
        $sign = $amount < 0 ? '-' : '';
        if ($a >= 1_000_000) {
            return $sign.'Rp'.rtrim(rtrim(number_format($a / 1_000_000, 1, ',', '.'), '0'), ',').'jt';
        }
        if ($a >= 1_000) {
            return $sign.'Rp'.rtrim(rtrim(number_format($a / 1_000, 1, ',', '.'), '0'), ',').'rb';
        }

        return $sign.'Rp'.number_format($a, 0, ',', '.');
    }
}

if (! function_exists('wib')) {
    /** Database menyimpan UTC; tampilan memakai WIB. */
    function wib(?CarbonInterface $time, string $format = 'd M Y, H.i'): string
    {
        return $time ? $time->copy()->timezone('Asia/Jakarta')->translatedFormat($format) : '-';
    }
}

if (! function_exists('mask_username')) {
    function mask_username(string $username): string
    {
        $len = mb_strlen($username);
        if ($len <= 2) {
            return mb_substr($username, 0, 1).'*';
        }

        return mb_substr($username, 0, 1).'***'.mb_substr($username, -1);
    }
}

if (! function_exists('category_class')) {
    /** Kelas warna konsisten per kategori (didefinisikan di app.css, aman untuk CSP tanpa inline style). */
    function category_class(string $category): string
    {
        return 'cat-'.(crc32(mb_strtolower($category)) % 8);
    }
}
