<?php

declare(strict_types=1);

use Vortex\Support\Env;

return [
    'default' => Env::get('STORAGE_DISK', 'local'),
    /** Disk used by {@see \Vortex\Files\Storage::publicRoot()} and {@see \Vortex\Files\Storage::deletePublic()}. */
    'public_disk' => 'public',
    /** Disk used by {@see \Vortex\Files\Storage::storeUpload()}. */
    'upload_disk' => 'public',
    'disks' => [
        'public' => [
            'driver' => 'local_public',
        ],
        'local' => [
            'driver' => 'local',
            'root' => 'storage/app',
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
];
