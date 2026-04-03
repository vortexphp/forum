<?php

declare(strict_types=1);

use Vortex\Support\Env;

return [
    'driver' => strtolower(trim((string) Env::get('MAIL_DRIVER', 'log'))),
    'from' => [
        'address' => (string) Env::get('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => (string) Env::get('MAIL_FROM_NAME', 'App'),
    ],
    'smtp' => [
        'host' => (string) Env::get('MAIL_HOST', '127.0.0.1'),
        'port' => (int) Env::get('MAIL_PORT', '587'),
        'username' => (string) Env::get('MAIL_USERNAME', ''),
        'password' => (string) Env::get('MAIL_PASSWORD', ''),
        'encryption' => strtolower(trim((string) Env::get('MAIL_ENCRYPTION', 'tls'))),
    ],
];
