<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\Thread;
use App\Models\User;
use App\Support\ForumBadgeService;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;

final class LikeHandler
{
    public function toggle(string $categorySlug, string $threadSlug, string $postId): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::notFound();
        }

        $thread = Thread::findByCategoryAndSlug((int) $category->id, $threadSlug);
        if ($thread === null) {
            return Response::notFound();
        }

        if (! Csrf::validate()) {
            if (Request::wantsJson()) {
                return Response::json(['ok' => false, 'message' => \trans('auth.csrf_invalid')], 419);
            }

            return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')]);
        }

        $uid = Session::authUserId();
        if ($uid === null) {
            if (Request::wantsJson()) {
                return Response::json(['ok' => false, 'message' => 'Unauthenticated'], 401);
            }

            return Response::redirect('/login', 302);
        }

        $post = Post::findInThread((int) $postId, (int) $thread->id);
        if ($post === null) {
            return Response::notFound();
        }

        $liked = PostLike::toggle((int) $post->id, (int) $uid);
        ForumBadgeService::recalculateForUser((int) $uid);
        ForumBadgeService::recalculateForUser((int) $post->user_id);
        if ($liked && (int) $post->user_id !== (int) $uid) {
            $actor = User::find((int) $uid);
            $actorName = (string) ($actor?->name ?? 'Someone');
            Notification::createForUser(
                (int) $post->user_id,
                (int) $uid,
                'post_liked',
                \trans('notifications.events.post_liked', ['user' => $actorName]),
                (string) ($thread->title ?? ''),
                route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]) . '#comments',
            );
        }

        $message = $liked ? \trans('forum.likes.added') : \trans('forum.likes.removed');
        $likesCount = PostLike::countForPost((int) $post->id);

        if (Request::wantsJson()) {
            return Response::json([
                'ok' => true,
                'liked' => $liked,
                'likes_count' => $likesCount,
                'message' => $message,
            ]);
        }

        return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302)
            ->with('status', $message);
    }
}
