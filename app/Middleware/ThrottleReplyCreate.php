<?php

declare(strict_types=1);

namespace App\Middleware;

use Vortex\Http\Middleware\Throttle;

final class ThrottleReplyCreate extends Throttle
{
    protected function profile(): string
    {
        return 'reply_create';
    }
}
