<?php

declare(strict_types=1);

use Vortex\Support\Env;

$database = Env::get('DB_DATABASE');
if ($database === null || $database === '') {
    $database = dirname(__DIR__) . '/storage/database.sqlite';
}

$driver = strtolower(trim((string) Env::get('DB_DRIVER', 'sqlite')));
$default = strtolower(trim((string) Env::get('DB_CONNECTION', 'default')));

return [
    'default' => $default,
    'connections' => [
        'default' => [
            'driver' => $driver,
            'host' => (string) Env::get('DB_HOST', '127.0.0.1'),
            'port' => (string) Env::get('DB_PORT', '3306'),
            'database' => $database,
            'username' => (string) Env::get('DB_USERNAME', ''),
            'password' => (string) Env::get('DB_PASSWORD', ''),
        ],
    ],
];
