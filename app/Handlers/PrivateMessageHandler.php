<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\PrivateMessage;
use App\Models\User;
use App\Support\ForumContent;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Pagination\Paginator;
use Vortex\Validation\Rule;
use Vortex\Validation\Validator;
use Vortex\View\View;

final class PrivateMessageHandler
{
    public function inbox(): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }

        $page = max(1, (int) Request::input('page', 1));
        $perPage = 20;
        $payload = PrivateMessage::paginateInbox((int) $user->id, $page, $perPage);
        $conversations = array_map(fn (array $row): array => array_merge($row, [
            'last_body' => ForumContent::normalizeMessageText((string) ($row['last_body'] ?? '')),
            'last_created_ago' => $this->formatAgo((string) ($row['last_created_at'] ?? '')),
        ]), $payload['items']);
        $lastPage = max(1, (int) ceil($payload['total'] / $perPage));
        $pagination = new Paginator($conversations, $payload['total'], $page, $perPage, $lastPage);
        $status = Session::flash('status');

        return View::html('messages.inbox', [
            'title' => \trans('messages.inbox.title'),
            'conversations' => $conversations,
            'pagination' => $pagination->withBasePath(route('messages.inbox')),
            'status' => is_string($status) ? $status : null,
            'unreadCount' => PrivateMessage::unreadCountForUser((int) $user->id),
        ]);
    }

    public function conversation(string $otherUserId): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }

        $otherId = (int) $otherUserId;
        if ($otherId <= 0 || $otherId === (int) $user->id) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $other = User::find($otherId);
        if ($other === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        PrivateMessage::markConversationRead((int) $user->id, $otherId);

        $page = max(1, (int) Request::input('page', 1));
        $perPage = 25;
        $payload = PrivateMessage::paginateConversation((int) $user->id, $otherId, $page, $perPage);
        $messages = array_reverse(array_map(fn (array $row): array => array_merge($row, [
            'body' => ForumContent::normalizeMessageText((string) ($row['body'] ?? '')),
            'created_ago' => $this->formatAgo((string) ($row['created_at'] ?? '')),
        ]), $payload['items']));
        $lastPage = max(1, (int) ceil($payload['total'] / $perPage));
        $pagination = new Paginator($messages, $payload['total'], $page, $perPage, $lastPage);

        $status = Session::flash('status');
        $errors = Session::flash('errors');
        $old = Session::flash('old');

        return View::html('messages.conversation', [
            'title' => \trans('messages.conversation.title', ['name' => (string) ($other->name ?? '')]),
            'otherUser' => $other,
            'messages' => $messages,
            'pagination' => $pagination->withBasePath(route('messages.show', ['user' => $otherId])),
            'status' => is_string($status) ? $status : null,
            'errors' => is_array($errors) ? $errors : [],
            'old' => is_array($old) ? $old : [],
        ]);
    }

    public function send(string $otherUserId): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }

        $otherId = (int) $otherUserId;
        if ($otherId <= 0 || $otherId === (int) $user->id) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $other = User::find($otherId);
        if ($other === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (! Csrf::validate()) {
            return Response::redirect(route('messages.show', ['user' => $otherId]), 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')]);
        }

        $data = ['body' => trim((string) Request::input('body', ''))];
        $validation = Validator::make(
            $data,
            ['body' => Rule::required(\trans('messages.validation.body_required'))->string()->max(5000)],
        );
        if ($validation->failed()) {
            return Response::redirect(route('messages.show', ['user' => $otherId]), 302)
                ->withErrors($validation->errors())
                ->withInput($data);
        }

        PrivateMessage::create([
            'sender_id' => (int) $user->id,
            'recipient_id' => $otherId,
            'body' => $data['body'],
            'read_at' => null,
        ]);

        return Response::redirect(route('messages.show', ['user' => $otherId]), 302)
            ->with('status', \trans('messages.sent'));
    }

    public function feed(string $otherUserId): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::json(['ok' => false, 'message' => 'Unauthenticated'], 401);
        }

        $otherId = (int) $otherUserId;
        if ($otherId <= 0 || $otherId === (int) $user->id) {
            return Response::json(['ok' => false, 'message' => 'Not Found'], 404);
        }

        $other = User::find($otherId);
        if ($other === null) {
            return Response::json(['ok' => false, 'message' => 'Not Found'], 404);
        }

        PrivateMessage::markConversationRead((int) $user->id, $otherId);
        $page = max(1, (int) Request::input('page', 1));
        $perPage = 25;
        $payload = PrivateMessage::paginateConversation((int) $user->id, $otherId, $page, $perPage);
        $messages = array_reverse(array_map(fn (array $row): array => array_merge($row, [
            'body' => ForumContent::normalizeMessageText((string) ($row['body'] ?? '')),
            'created_ago' => $this->formatAgo((string) ($row['created_at'] ?? '')),
        ]), $payload['items']));
        $lastPage = max(1, (int) ceil($payload['total'] / $perPage));

        return Response::json([
            'ok' => true,
            'items' => $messages,
            'page' => $page,
            'has_more' => $page < $lastPage,
        ]);
    }

    public function sendJson(string $otherUserId): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::json(['ok' => false, 'message' => 'Unauthenticated'], 401);
        }

        $otherId = (int) $otherUserId;
        if ($otherId <= 0 || $otherId === (int) $user->id) {
            return Response::json(['ok' => false, 'message' => 'Not Found'], 404);
        }

        $other = User::find($otherId);
        if ($other === null) {
            return Response::json(['ok' => false, 'message' => 'Not Found'], 404);
        }

        if (! Csrf::validate()) {
            return Response::json(['ok' => false, 'message' => \trans('auth.csrf_invalid')], 419);
        }

        $data = ['body' => trim((string) Request::input('body', ''))];
        $validation = Validator::make(
            $data,
            ['body' => Rule::required(\trans('messages.validation.body_required'))->string()->max(5000)],
        );
        if ($validation->failed()) {
            return Response::json([
                'ok' => false,
                'errors' => $validation->errors(),
            ], 422);
        }

        PrivateMessage::create([
            'sender_id' => (int) $user->id,
            'recipient_id' => $otherId,
            'body' => $data['body'],
            'read_at' => null,
        ]);

        return Response::json(['ok' => true, 'message' => \trans('messages.sent')], 201);
    }

    private function currentUser(): ?User
    {
        $uid = Session::authUserId();

        return $uid === null ? null : User::find($uid);
    }

    private function formatAgo(string $timestamp): string
    {
        $ts = strtotime($timestamp);
        if ($ts === false) {
            return '1m';
        }

        $diff = max(0, time() - $ts);
        if ($diff < 60) {
            return '1m';
        }
        if ($diff < 3600) {
            return (string) floor($diff / 60) . 'm';
        }
        if ($diff < 86400) {
            return (string) floor($diff / 3600) . 'h';
        }
        if ($diff < 2592000) {
            return (string) floor($diff / 86400) . 'd';
        }
        if ($diff < 31536000) {
            return (string) floor($diff / 2592000) . 'mo';
        }

        return (string) floor($diff / 31536000) . 'y';
    }

}
