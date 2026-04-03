<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Thread;
use App\Models\ThreadBookmark;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Pagination\Paginator;
use Vortex\View\View;

final class BookmarkHandler
{
    public function index(): Response
    {
        $uid = Session::authUserId();
        if ($uid === null) {
            return Response::redirect('/login', 302);
        }

        $page = max(1, (int) Request::input('page', 1));
        $perPage = 20;
        $payload = ThreadBookmark::paginateForUser($uid, $page, $perPage);
        $lastPage = max(1, (int) ceil($payload['total'] / $perPage));
        $pagination = new Paginator($payload['items'], $payload['total'], $page, $perPage, $lastPage);
        $status = Session::flash('status');

        return View::html('forum.bookmarks', [
            'title' => \trans('forum.bookmarks.title'),
            'threads' => $payload['items'],
            'pagination' => $pagination->withBasePath(route('forum.bookmarks')),
            'status' => is_string($status) ? $status : null,
        ]);
    }

    public function toggle(string $categorySlug, string $threadSlug): Response
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
            return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')]);
        }

        $uid = Session::authUserId();
        if ($uid === null) {
            return Response::redirect('/login', 302);
        }

        $bookmarked = ThreadBookmark::toggle((int) $thread->id, $uid);

        return Response::redirect(route('forum.thread.show', ['category' => $categorySlug, 'thread' => $threadSlug]), 302)
            ->with('status', $bookmarked ? \trans('forum.bookmarks.added') : \trans('forum.bookmarks.removed'));
    }
}
