<?php

declare(strict_types=1);

/**
 * Fallback when the web root is the project directory (not recommended).
 * Prefer pointing the vhost document root at /public.
 */
require __DIR__ . '/public/index.php';
