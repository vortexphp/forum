<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Closure;
use Vortex\Contracts\Middleware;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;

final class RequireModerator implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $uid = Session::authUserId();
        if ($uid === null) {
            return Response::redirect('/login', 302);
        }

        $user = User::find($uid);
        if ($user === null || ! $user->isModerator()) {
            return Response::make('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return $next($request);
    }
}
