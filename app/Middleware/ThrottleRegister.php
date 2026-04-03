<?php

declare(strict_types=1);

namespace App\Middleware;

use Vortex\Http\Middleware\Throttle;

final class ThrottleRegister extends Throttle
{
    protected function profile(): string
    {
        return 'register';
    }
}
