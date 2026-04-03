<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Pagination\Paginator;
use Vortex\Validation\Validator;
use Vortex\View\View;

final class ThreadHandler
{
    public function show(string $categorySlug, string $threadSlug): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $thread = Thread::findByCategoryAndSlug((int) $category->id, $threadSlug);
        if ($thread === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $threadData = Thread::findWithAuthor((int) $thread->id);
        if ($threadData === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $page = max(1, (int) Request::input('page', 1));
        $postsPayload = Post::paginateForThread((int) $thread->id, $page, 20);
        $lastPage = max(1, (int) ceil($postsPayload['total'] / 20));
        $pagination = new Paginator($postsPayload['items'], $postsPayload['total'], $page, 20, $lastPage);

        $status = Session::flash('status');
        $errors = Session::flash('errors');
        $old = Session::flash('old');

        return View::html('forum.thread_show', [
            'title' => (string) $thread->title,
            'category' => $category,
            'thread' => $threadData,
            'posts' => $postsPayload['items'],
            'pagination' => $pagination->withBasePath(route('forum.thread.show', [
                'category' => $category->slug,
                'thread' => $thread->slug,
            ])),
            'status' => is_string($status) ? $status : null,
            'errors' => is_array($errors) ? $errors : [],
            'old' => is_array($old) ? $old : [],
        ]);
    }

    public function create(string $categorySlug): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
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
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect(route('forum.thread.create', ['category' => $category->slug]), 302);
        }

        if ((int) ($category->is_locked ?? 0) === 1) {
            Session::flash('errors', ['_form' => \trans('forum.categories.locked')]);

            return Response::redirect(route('forum.category', ['category' => $category->slug]), 302);
        }

        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }

        $data = [
            'title' => trim((string) Request::input('title', '')),
            'body' => trim((string) Request::input('body', '')),
        ];

        $validation = Validator::make(
            $data,
            ['title' => 'required|string|max:180', 'body' => 'required|string|max:20000'],
            [
                'title.required' => \trans('forum.validation.thread_title_required'),
                'body.required' => \trans('forum.validation.thread_body_required'),
            ],
        );
        if ($validation->failed()) {
            Session::flash('errors', $validation->errors());
            Session::flash('old', $data);

            return Response::redirect(route('forum.thread.create', ['category' => $category->slug]), 302);
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

        Session::flash('status', \trans('forum.thread.created'));

        return Response::redirect(route('forum.thread.show', [
            'category' => $category->slug,
            'thread' => $thread->slug,
        ]), 302);
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
