<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Post;
use App\Models\Thread;
use Vortex\Http\Csrf;
use Vortex\Http\Response;
use Vortex\Http\Session;

final class ModerationHandler
{
    public function toggleLock(string $categorySlug, string $threadSlug): Response
    {
        $resolved = $this->resolveThread($categorySlug, $threadSlug);
        if ($resolved === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        [$category, $thread] = $resolved;

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
        }

        $locked = (int) ($thread->is_locked ?? 0) === 1;
        Thread::updateRecord((int) $thread->id, ['is_locked' => $locked ? 0 : 1]);
        Session::flash('status', $locked ? \trans('forum.moderation.unlocked') : \trans('forum.moderation.locked'));

        return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
    }

    public function togglePin(string $categorySlug, string $threadSlug): Response
    {
        $resolved = $this->resolveThread($categorySlug, $threadSlug);
        if ($resolved === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        [$category, $thread] = $resolved;

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
        }

        $pinned = (int) ($thread->is_pinned ?? 0) === 1;
        Thread::updateRecord((int) $thread->id, ['is_pinned' => $pinned ? 0 : 1]);
        Session::flash('status', $pinned ? \trans('forum.moderation.unpinned') : \trans('forum.moderation.pinned'));

        return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
    }

    public function deleteThread(string $categorySlug, string $threadSlug): Response
    {
        $resolved = $this->resolveThread($categorySlug, $threadSlug);
        if ($resolved === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        [$category, $thread] = $resolved;

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
        }

        Thread::deleteId((int) $thread->id);
        Session::flash('status', \trans('forum.moderation.thread_deleted'));

        return Response::redirect(route('forum.category', ['category' => $category->slug]), 302);
    }

    public function deletePost(string $categorySlug, string $threadSlug, string $postId): Response
    {
        $resolved = $this->resolveThread($categorySlug, $threadSlug);
        if ($resolved === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        [$category, $thread] = $resolved;

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
        }

        $post = Post::findInThread((int) $postId, (int) $thread->id);
        if ($post === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        Post::deleteId((int) $post->id);
        Thread::touchAfterReply((int) $thread->id);
        Session::flash('status', \trans('forum.moderation.post_deleted'));

        return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
    }

    /**
     * @return array{0: Category, 1: Thread}|null
     */
    private function resolveThread(string $categorySlug, string $threadSlug): ?array
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return null;
        }

        $thread = Thread::findByCategoryAndSlug((int) $category->id, $threadSlug);
        if ($thread === null) {
            return null;
        }

        return [$category, $thread];
    }

    private function threadUrl(string $categorySlug, string $threadSlug): string
    {
        return route('forum.thread.show', [
            'category' => $categorySlug,
            'thread' => $threadSlug,
        ]);
    }
}
