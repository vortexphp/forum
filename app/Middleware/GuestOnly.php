<?php

declare(strict_types=1);

namespace App\Middleware;

use Closure;
use Vortex\Contracts\Middleware;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;

final class GuestOnly implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::authUserId() !== null) {
            return Response::redirect('/', 302);
        }

        return $next($request);
    }
}
