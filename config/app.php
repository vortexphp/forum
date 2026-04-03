<?php

declare(strict_types=1);

use Vortex\Support\Env;

$localesLine = (string) Env::get('APP_LOCALES', 'en,bg');

return [
    'name' => Env::get('APP_NAME', 'Vortex'),
    'debug' => filter_var(Env::get('APP_DEBUG', '0'), FILTER_VALIDATE_BOOLEAN),
    'url' => Env::get('APP_URL', 'http://localhost'),
    'csp_header' => Env::get('CSP_HEADER', ''),
    'locale' => Env::get('APP_LOCALE', 'en'),
    'fallback_locale' => Env::get('APP_FALLBACK_LOCALE', 'en'),
    'locales' => array_values(array_filter(array_map(trim(...), explode(',', $localesLine)))),
    'middleware' => [
        App\Middleware\TrimTrailingSlash::class,
        App\Middleware\SetLocale::class,
        App\Middleware\ShareAuthUser::class,
    ],
];
