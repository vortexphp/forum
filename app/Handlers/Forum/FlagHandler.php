<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\ContentFlag;
use App\Models\Post;
use App\Models\Thread;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;

final class FlagHandler
{
    public function flagThread(string $categorySlug, string $threadSlug): Response
    {
        $category = Category::findBySlug($categorySlug);
        $thread = $category === null ? null : Thread::findByCategoryAndSlug((int) $category->id, $threadSlug);
        if ($category === null || $thread === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return $this->flag('thread', (int) $thread->id, $categorySlug, $threadSlug);
    }

    public function flagPost(string $categorySlug, string $threadSlug, string $postId): Response
    {
        $category = Category::findBySlug($categorySlug);
        $thread = $category === null ? null : Thread::findByCategoryAndSlug((int) $category->id, $threadSlug);
        if ($category === null || $thread === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $post = Post::findInThread((int) $postId, (int) $thread->id);
        if ($post === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return $this->flag('post', (int) $post->id, $categorySlug, $threadSlug);
    }

    private function flag(string $type, int $targetId, string $categorySlug, string $threadSlug): Response
    {
        if (! Csrf::validate()) {
            return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')]);
        }

        $uid = Session::authUserId();
        if ($uid === null) {
            return Response::redirect('/login', 302);
        }

        if (ContentFlag::hasUserFlagged((int) $uid, $type, $targetId)) {
            return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302)
                ->with('status', \trans('forum.flags.already_flagged'));
        }

        $reason = trim((string) Request::input('reason', 'inappropriate'));
        if ($reason === '') {
            $reason = 'inappropriate';
        }

        ContentFlag::create([
            'reporter_id' => (int) $uid,
            'target_type' => $type,
            'target_id' => $targetId,
            'reason' => substr($reason, 0, 80),
            'status' => 'open',
        ]);

        return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302)
            ->with('status', \trans('forum.flags.reported'));
    }
}
