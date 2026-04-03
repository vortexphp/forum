<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\Thread;
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
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302);
        }

        $uid = Session::authUserId();
        if ($uid === null) {
            return Response::redirect('/login', 302);
        }

        $post = Post::findInThread((int) $postId, (int) $thread->id);
        if ($post === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $liked = PostLike::toggle((int) $post->id, (int) $uid);
        Session::flash('status', $liked ? \trans('forum.likes.added') : \trans('forum.likes.removed'));

        return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302);
    }
}
