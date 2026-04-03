<?php

declare(strict_types=1);

use Vortex\Support\Env;

return [
    'max_upload_bytes' => (int) Env::get('UPLOAD_MAX_BYTES', '2097152'),
    // Profile photo: directory under public/ and allowed MIME → extension map.
    'avatar' => [
        'directory' => 'uploads/avatars',
        'mime_extensions' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ],
    ],
];
