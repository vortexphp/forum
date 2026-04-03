<?php

declare(strict_types=1);

namespace App\Middleware;

use Closure;
use Vortex\Contracts\Middleware;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Support\UrlHelp;
use Vortex\Http\Session;

final class RequireAuth implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::authUserId() === null) {
            return Response::redirect(
                UrlHelp::withQuery('/login', ['next' => Request::path()]),
                302,
            );
        }

        return $next($request);
    }
}
