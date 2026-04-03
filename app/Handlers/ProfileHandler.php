<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\PrivateMessage;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Support\ForumBadgeService;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\View\View;

final class ProfileHandler
{
    public function show(string $userId): Response
    {
        $uid = (int) $userId;
        if ($uid <= 0) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $user = User::find($uid);
        if ($user === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        ForumBadgeService::recalculateForUser($uid);
        $authUserId = Session::authUserId();

        return View::html('profile.show', [
            'title' => \trans('profile.title', ['name' => (string) ($user->name ?? '')]),
            'profileUser' => $user,
            'stats' => User::publicStats($uid),
            'badges' => $user->publicBadges(),
            'recentThreads' => Thread::latestByUser($uid, 8),
            'recentPosts' => Post::latestByUser($uid, 10),
            'unreadMessagesCount' => $authUserId === null ? 0 : PrivateMessage::unreadCountForUser($authUserId),
        ]);
    }
}
