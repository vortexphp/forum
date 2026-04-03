<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\Notification;
use App\Models\PrivateMessage;
use App\Models\User;
use Closure;
use Vortex\Contracts\Middleware;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Support\Benchmark;
use Vortex\View\View;
use Throwable;

/**
 * Exposes {@see $authUser} (nullable) and {@see $csrfToken} to all views.
 */
final class ShareAuthUser implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $uid = Session::authUserId();
        $user = null;
        $unreadMessages = 0;
        $unreadNotifications = 0;

        try {
            $user = $uid === null ? null : User::find($uid);
            if ($uid !== null && $user === null) {
                Session::forget('auth_user_id');
            }
            if ($uid !== null) {
                $unreadMessages = PrivateMessage::unreadCountForUser($uid);
                $unreadNotifications = Notification::unreadCountForUser($uid);
            }
        } catch (Throwable) {
            // During fresh install (or partial migrations), shared auth data must not break page rendering.
            $user = null;
            $unreadMessages = 0;
            $unreadNotifications = 0;
        }

        View::share('authUser', $user);
        View::share('unreadMessagesCount', $unreadMessages);
        View::share('unreadNotificationsCount', $unreadNotifications);
        View::share('csrfToken', Csrf::token());
        View::share('renderTimeMs', Benchmark::has('request') ? Benchmark::elapsedMs('request', 2) : 0.0);

        return $next($request);
    }
}
