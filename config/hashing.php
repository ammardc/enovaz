<?php

// Password login di-hash dengan Argon2id (salt unik dibuat otomatis oleh PHP).
return [
    'driver' => 'argon2id',

    'bcrypt' => [
        'rounds' => 12,
        'verify' => true,
    ],

    'argon' => [
        'memory' => 65536, // KiB
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

    'rehash_on_login' => true,
];
