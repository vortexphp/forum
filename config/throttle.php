<?php

declare(strict_types=1);

return [
    'default' => [
        'max_attempts' => 120,
        'decay_seconds' => 60,
    ],
    'login' => [
        'max_attempts' => 10,
        'decay_seconds' => 60,
    ],
    'register' => [
        'max_attempts' => 5,
        'decay_seconds' => 3600,
    ],
];
