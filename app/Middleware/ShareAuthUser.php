<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Closure;
use Vortex\Contracts\Middleware;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\View\View;

/**
 * Exposes {@see $authUser} (nullable) and {@see $csrfToken} to all views.
 */
final class ShareAuthUser implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $uid = Session::authUserId();
        $user = $uid === null ? null : User::find($uid);
        if ($uid !== null && $user === null) {
            Session::forget('auth_user_id');
        }

        View::share('authUser', $user);
        View::share('csrfToken', Csrf::token());

        return $next($request);
    }
}
