<?php

declare(strict_types=1);

use Vortex\Support\Env;

$path = Env::get('CACHE_PATH');
if ($path === null || trim((string) $path) === '') {
    $path = dirname(__DIR__) . '/storage/cache/data';
}

$driver = strtolower(trim((string) Env::get('CACHE_DRIVER', 'file')));
$default = strtolower(trim((string) Env::get('CACHE_STORE', $driver)));

return [
    'default' => $default,
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => $path,
            'prefix' => (string) Env::get('CACHE_PREFIX', 'vortex:'),
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
];
