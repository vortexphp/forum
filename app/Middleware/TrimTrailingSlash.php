<?php

declare(strict_types=1);

namespace App\Middleware;

use Closure;
use Vortex\Contracts\Middleware;
use Vortex\Http\Request;
use Vortex\Http\Response;

final class TrimTrailingSlash implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Request::path() !== '/' && str_ends_with(Request::path(), '/')) {
            $target = rtrim(Request::path(), '/') ?: '/';

            return Response::redirect($target, 301);
        }

        return $next($request);
    }
}
