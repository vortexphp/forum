<?php

declare(strict_types=1);

namespace App\Middleware;

use Vortex\Http\Middleware\Throttle;

final class ThrottleLogin extends Throttle
{
    protected function profile(): string
    {
        return 'login';
    }
}
