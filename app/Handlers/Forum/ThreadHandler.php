<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\PostLike;
use App\Models\ThreadBookmark;
use App\Support\ForumBadgeService;
use App\Support\ForumContent;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Pagination\Paginator;
use Vortex\Validation\Rule;
use Vortex\Validation\Validator;
use Vortex\View\View;

final class ThreadHandler
{
    public function show(string $categorySlug, string $threadSlug): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::notFound();
        }

        $thread = Thread::findByCategoryAndSlug((int) $category->id, $threadSlug);
        if ($thread === null) {
            return Response::notFound();
        }

        $threadData = Thread::findWithAuthor((int) $thread->id);
        if ($threadData === null) {
            return Response::notFound();
        }

        $page = max(1, (int) Request::input('page', 1));
        $commentsPerPage = 10;
        $postsPayload = Post::paginateForThread((int) $thread->id, $page, $commentsPerPage);
        $uid = Session::authUserId();
        $postIds = [];
        foreach ($postsPayload['items'] as $row) {
            $postIds[] = (int) ($row['id'] ?? 0);
        }
        $likedMap = $uid === null ? [] : PostLike::likedMapForUser($uid, $postIds);
        $postsRendered = [];
        foreach ($postsPayload['items'] as $row) {
            $row['body_html'] = ForumContent::render((string) ($row['body'] ?? ''));
            $row['liked_by_auth_user'] = isset($likedMap[(int) ($row['id'] ?? 0)]);
            $postsRendered[] = $row;
        }
        $bookmarkedByAuthUser = $uid === null ? false : ThreadBookmark::hasForUser((int) $thread->id, $uid);

        $lastPage = max(1, (int) ceil($postsPayload['total'] / $commentsPerPage));
        $pagination = new Paginator($postsRendered, $postsPayload['total'], $page, $commentsPerPage, $lastPage);

        $status = Session::flash('status');
        $errors = Session::flash('errors');
        $old = Session::flash('old');

        return View::html('forum.thread_show', [
            'title' => (string) $thread->title,
            'category' => $category,
            'thread' => $threadData + ['body_html' => ForumContent::render((string) ($threadData['body'] ?? ''))],
            'tags' => Thread::tags((int) $thread->id),
            'posts' => $postsRendered,
            'pagination' => $pagination->withBasePath(route('forum.thread.show', [
                'category' => $category->slug,
                'thread' => $thread->slug,
            ])),
            'bookmarkedByAuthUser' => $bookmarkedByAuthUser,
            'status' => is_string($status) ? $status : null,
            'errors' => is_array($errors) ? $errors : [],
            'old' => is_array($old) ? $old : [],
        ]);
    }

    public function create(string $categorySlug): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::notFound();
        }

        $errors = Session::flash('errors');
        $old = Session::flash('old');

        return View::html('forum.thread_create', [
            'title' => \trans('forum.thread.create_title'),
            'category' => $category,
            'errors' => is_array($errors) ? $errors : [],
            'old' => is_array($old) ? $old : [],
        ]);
    }

    public function store(string $categorySlug): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::notFound();
        }

        if (! Csrf::validate()) {
            return Response::redirect(route('forum.thread.create', ['category' => $category->slug]), 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')]);
        }

        if ((int) ($category->is_locked ?? 0) === 1) {
            return Response::redirect(route('forum.category', ['category' => $category->slug]), 302)
                ->withErrors(['_form' => \trans('forum.categories.locked')]);
        }

        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }

        $data = [
            'title' => trim((string) Request::input('title', '')),
            'body' => trim((string) Request::input('body', '')),
            'tags' => trim((string) Request::input('tags', '')),
        ];

        $validation = Validator::make(
            $data,
            [
                'title' => Rule::required(\trans('forum.validation.thread_title_required'))->string()->max(180),
                'body' => Rule::required(\trans('forum.validation.thread_body_required'))->string()->max(20000),
                'tags' => Rule::nullable()->string()->max(200),
            ],
        );
        if ($validation->failed()) {
            return Response::redirect(route('forum.thread.create', ['category' => $category->slug]), 302)
                ->withErrors($validation->errors())
                ->withInput($data);
        }

        $slug = $this->uniqueSlug((int) $category->id, $data['title']);
        $now = gmdate('Y-m-d H:i:s');
        $thread = Thread::create([
            'category_id' => (int) $category->id,
            'user_id' => (int) $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'body' => $data['body'],
            'is_locked' => 0,
            'is_pinned' => 0,
            'reply_count' => 0,
            'last_post_at' => $now,
        ]);

        Post::create([
            'thread_id' => (int) $thread->id,
            'user_id' => (int) $user->id,
            'body' => $data['body'],
            'is_edited' => 0,
            'edited_at' => null,
        ]);
        Thread::syncTags((int) $thread->id, $this->parseTags($data['tags']));
        ForumBadgeService::recalculateForUser((int) $user->id);

        return Response::redirect(route('forum.thread.show', [
            'category' => $category->slug,
            'thread' => $thread->slug,
        ]), 302)->with('status', \trans('forum.thread.created'));
    }

    /**
     * @return list<string>
     */
    private function parseTags(string $raw): array
    {
        $parts = array_map(static fn (string $v): string => trim($v), explode(',', $raw));
        $out = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $out[] = substr($part, 0, 40);
            if (count($out) >= 6) {
                break;
            }
        }

        return array_values(array_unique($out));
    }

    private function uniqueSlug(int $categoryId, string $title): string
    {
        $base = $this->slugify($title);
        $slug = $base;
        $i = 2;
        while (Thread::findByCategoryAndSlug($categoryId, $slug) !== null) {
            $slug = $base . '-' . $i;
            ++$i;
        }

        return $slug;
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'thread';
    }

    private function currentUser(): ?User
    {
        $uid = Session::authUserId();

        return $uid === null ? null : User::find($uid);
    }
}
