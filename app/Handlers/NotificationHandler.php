<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\Notification;
use App\Models\User;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Pagination\Paginator;
use Vortex\View\View;

final class NotificationHandler
{
    public function index(): Response
    {
        $uid = Session::authUserId();
        if ($uid === null) {
            return Response::redirect('/login', 302);
        }

        $user = User::find($uid);
        if ($user === null) {
            Session::forget('auth_user_id');

            return Response::redirect('/login', 302);
        }

        Notification::markAllReadForUser((int) $user->id);

        $page = max(1, (int) Request::input('page', 1));
        $perPage = 20;
        $payload = Notification::paginateForUser((int) $user->id, $page, $perPage);
        $lastPage = max(1, (int) ceil($payload['total'] / $perPage));
        $pagination = new Paginator($payload['items'], $payload['total'], $page, $perPage, $lastPage);

        return View::html('notifications.index', [
            'title' => \trans('notifications.title'),
            'notifications' => $payload['items'],
            'pagination' => $pagination->withBasePath(route('notifications.index')),
        ]);
    }
}
