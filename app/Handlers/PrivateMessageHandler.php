<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\PrivateMessage;
use App\Models\User;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Pagination\Paginator;
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
        $lastPage = max(1, (int) ceil($payload['total'] / $perPage));
        $pagination = new Paginator($payload['items'], $payload['total'], $page, $perPage, $lastPage);
        $status = Session::flash('status');

        return View::html('messages.inbox', [
            'title' => \trans('messages.inbox.title'),
            'conversations' => $payload['items'],
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
        $messages = array_reverse($payload['items']);
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
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect(route('messages.show', ['user' => $otherId]), 302);
        }

        $data = ['body' => trim((string) Request::input('body', ''))];
        $validation = Validator::make(
            $data,
            ['body' => 'required|string|max:5000'],
            ['body.required' => \trans('messages.validation.body_required')],
        );
        if ($validation->failed()) {
            Session::flash('errors', $validation->errors());
            Session::flash('old', $data);

            return Response::redirect(route('messages.show', ['user' => $otherId]), 302);
        }

        PrivateMessage::create([
            'sender_id' => (int) $user->id,
            'recipient_id' => $otherId,
            'body' => $data['body'],
            'read_at' => null,
        ]);

        Session::flash('status', \trans('messages.sent'));

        return Response::redirect(route('messages.show', ['user' => $otherId]), 302);
    }

    private function currentUser(): ?User
    {
        $uid = Session::authUserId();

        return $uid === null ? null : User::find($uid);
    }
}
