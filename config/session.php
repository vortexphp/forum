<?php

declare(strict_types=1);

use Vortex\Support\Env;

return [
    'default' => strtolower(trim((string) Env::get('SESSION_STORE', 'native'))),
    'stores' => [
        'native' => [
            'driver' => 'native',
            'name' => (string) Env::get('SESSION_NAME', 'pc_session'),
            'lifetime' => (int) Env::get('SESSION_LIFETIME', '7200'),
            'secure' => filter_var(Env::get('SESSION_SECURE', '0'), FILTER_VALIDATE_BOOLEAN),
            'samesite' => (string) Env::get('SESSION_SAMESITE', 'Lax'),
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
];
