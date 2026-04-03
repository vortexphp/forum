<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Support\ForumBadgeService;
use App\Support\ForumContent;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Validation\Validator;
use Vortex\View\View;

final class PostHandler
{
    public function store(string $categorySlug, string $threadSlug): Response
    {
        $resolved = $this->resolveThread($categorySlug, $threadSlug);
        if ($resolved === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        [$category, $thread] = $resolved;

        if (! Csrf::validate()) {
            if ($this->wantsJson()) {
                return $this->json(['ok' => false, 'message' => \trans('auth.csrf_invalid')], 419);
            }

            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
        }

        $user = $this->currentUser();
        if ($user === null) {
            if ($this->wantsJson()) {
                return $this->json(['ok' => false, 'message' => 'Unauthenticated'], 401);
            }

            return Response::redirect('/login', 302);
        }

        if ((int) ($thread->is_locked ?? 0) === 1 && ! $user->isModerator()) {
            if ($this->wantsJson()) {
                return $this->json(['ok' => false, 'message' => \trans('forum.thread.locked')], 423);
            }

            Session::flash('errors', ['_form' => \trans('forum.thread.locked')]);

            return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
        }

        $data = ['body' => trim((string) Request::input('body', ''))];
        $validation = Validator::make(
            $data,
            ['body' => 'required|string|max:20000'],
            ['body.required' => \trans('forum.validation.reply_required')],
        );
        if ($validation->failed()) {
            if ($this->wantsJson()) {
                return $this->json([
                    'ok' => false,
                    'errors' => $validation->errors(),
                ], 422);
            }

            Session::flash('errors', $validation->errors());
            Session::flash('old', $data);

            return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
        }

        $post = Post::create([
            'thread_id' => (int) $thread->id,
            'user_id' => (int) $user->id,
            'body' => $data['body'],
            'is_edited' => 0,
            'edited_at' => null,
        ]);

        Thread::touchAfterReply((int) $thread->id);
        ForumBadgeService::recalculateForUser((int) $user->id);
        if ((int) ($thread->user_id ?? 0) > 0 && (int) ($thread->user_id ?? 0) !== (int) $user->id) {
            Notification::createForUser(
                (int) $thread->user_id,
                (int) $user->id,
                'thread_replied',
                \trans('notifications.events.thread_replied', [
                    'user' => (string) ($user->name ?? 'Someone'),
                    'thread' => (string) ($thread->title ?? ''),
                ]),
                null,
                route('forum.thread.show', ['category' => (string) $category->slug, 'thread' => (string) $thread->slug]) . '#comments',
            );
        }

        $message = \trans('forum.reply.created');

        if ($this->wantsJson()) {
            $primaryBadge = null;
            foreach ($user->publicBadges() as $badge) {
                if ($badge !== '' && $badge !== 'moderator') {
                    $primaryBadge = $badge;
                    break;
                }
            }

            return $this->json([
                'ok' => true,
                'message' => $message,
                'post' => [
                    'id' => (int) ($post->id ?? 0),
                    'author_id' => (int) ($user->id ?? 0),
                    'author_name' => (string) ($user->name ?? ''),
                    'author_avatar' => (string) ($user->avatar ?? ''),
                    'author_role' => (string) ($user->role ?? 'member'),
                    'author_primary_badge' => $primaryBadge,
                    'author_primary_badge_label' => $primaryBadge === null ? null : \trans('badges.' . $primaryBadge),
                    'created_at' => (string) gmdate('Y-m-d H:i:s'),
                    'body_html' => ForumContent::render($data['body']),
                    'likes_count' => 0,
                    'liked_by_auth_user' => false,
                    'is_edited' => false,
                    'edited_at' => null,
                    'like_url' => route('forum.post.like', ['category' => (string) $category->slug, 'thread' => (string) $thread->slug, 'post' => (int) ($post->id ?? 0)]),
                    'flag_url' => route('forum.flag.post', ['category' => (string) $category->slug, 'thread' => (string) $thread->slug, 'post' => (int) ($post->id ?? 0)]),
                    'edit_url' => route('forum.post.edit', ['category' => (string) $category->slug, 'thread' => (string) $thread->slug, 'post' => (int) ($post->id ?? 0)]),
                    'delete_url' => route('forum.moderation.delete_post', ['category' => (string) $category->slug, 'thread' => (string) $thread->slug, 'post' => (int) ($post->id ?? 0)]),
                    'can_edit' => true,
                    'can_delete' => $user->isModerator(),
                    'profile_url' => route('profile.show', ['user' => (int) ($user->id ?? 0)]),
                ],
            ]);
        }

        Session::flash('status', $message);

        return Response::redirect($this->threadUrl((string) $category->slug, (string) $thread->slug), 302);
    }

    public function edit(string $categorySlug, string $threadSlug, string $postId): Response
    {
        $resolved = $this->resolveThread($categorySlug, $threadSlug);
        if ($resolved === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        [$category, $thread] = $resolved;

        $post = Post::findInThread((int) $postId, (int) $thread->id);
        if ($post === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }
        if ((int) $post->user_id !== (int) $user->id && ! $user->isModerator()) {
            return Response::make('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $errors = Session::flash('errors');
        $old = Session::flash('old');

        return View::html('forum.post_edit', [
            'title' => \trans('forum.post.edit_title'),
            'category' => $category,
            'thread' => $thread,
            'post' => $post,
            'errors' => is_array($errors) ? $errors : [],
            'old' => is_array($old) ? $old : [],
        ]);
    }

    public function update(string $categorySlug, string $threadSlug, string $postId): Response
    {
        $resolved = $this->resolveThread($categorySlug, $threadSlug);
        if ($resolved === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        [$category, $thread] = $resolved;

        $post = Post::findInThread((int) $postId, (int) $thread->id);
        if ($post === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect(route('forum.post.edit', [
                'category' => $category->slug,
                'thread' => $thread->slug,
                'post' => (int) $post->id,
            ]), 302);
        }

        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }
        if ((int) $post->user_id !== (int) $user->id && ! $user->isModerator()) {
            return Response::make('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $data = ['body' => trim((string) Request::input('body', ''))];
        $validation = Validator::make(
            $data,
            ['body' => 'required|string|max:20000'],
            ['body.required' => \trans('forum.validation.reply_required')],
        );
        if ($validation->failed()) {
            Session::flash('errors', $validation->errors());
            Session::flash('old', $data);

            return Response::redirect(route('forum.post.edit', [
                'category' => $category->slug,
                'thread' => $thread->slug,
                'post' => (int) $post->id,
            ]), 302);
        }

        Post::updateRecord((int) $post->id, [
            'body' => $data['body'],
            'is_edited' => 1,
            'edited_at' => gmdate('Y-m-d H:i:s'),
        ]);

        Session::flash('status', \trans('forum.post.updated'));

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

    private function currentUser(): ?User
    {
        $uid = Session::authUserId();

        return $uid === null ? null : User::find($uid);
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
