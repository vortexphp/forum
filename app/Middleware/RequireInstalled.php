<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\InstallState;
use Closure;
use Vortex\Contracts\Middleware;
use Vortex\Http\Request;
use Vortex\Http\Response;

final class RequireInstalled implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallState::isInstalled()) {
            return $next($request);
        }

        $path = '/' . ltrim((string) Request::path(), '/');
        if ($path === '//') {
            $path = '/';
        }

        if ($path === '/install') {
            return $next($request);
        }

        return Response::redirect('/install', 302);
    }
}
