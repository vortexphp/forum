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
use Vortex\Http\Response;
use Vortex\Http\Session;

final class LikeHandler
{
    public function toggle(string $categorySlug, string $threadSlug, string $postId): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $thread = Thread::findByCategoryAndSlug((int) $category->id, $threadSlug);
        if ($thread === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (! Csrf::validate()) {
            if ($this->wantsJson()) {
                return $this->json(['ok' => false, 'message' => \trans('auth.csrf_invalid')], 419);
            }

            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302);
        }

        $uid = Session::authUserId();
        if ($uid === null) {
            if ($this->wantsJson()) {
                return $this->json(['ok' => false, 'message' => 'Unauthenticated'], 401);
            }

            return Response::redirect('/login', 302);
        }

        $post = Post::findInThread((int) $postId, (int) $thread->id);
        if ($post === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
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

        if ($this->wantsJson()) {
            return $this->json([
                'ok' => true,
                'liked' => $liked,
                'likes_count' => $likesCount,
                'message' => $message,
            ]);
        }

        Session::flash('status', $message);

        return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302);
    }

    private function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if ($accept !== '' && str_contains($accept, 'application/json')) {
            return true;
        }

        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return $requestedWith === 'xmlhttprequest';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status = 200): Response
    {
        return Response::make(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $status,
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }
}
