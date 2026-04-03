<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Models\User;
use Vortex\Crypto\Password;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Support\UrlHelp;
use Vortex\Validation\Validator;
use Vortex\View\View;

final class LoginHandler
{
    public function show(): Response
    {
        $errors = Session::flash('errors');
        $status = Session::flash('status');
        $old = Session::flash('old');
        $next = self::safeNext(Request::query()['next'] ?? '/');

        return View::html('auth.login', [
            'title' => \trans('auth.login.title'),
            'errors' => is_array($errors) ? $errors : [],
            'status' => is_string($status) ? $status : null,
            'old' => is_array($old) ? $old : [],
            'next' => $next,
        ]);
    }

    public function store(): Response
    {
        $next = self::safeNext(Request::input('next', '/'));

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);

            return Response::redirect(UrlHelp::withQuery('/login', ['next' => $next]), 302);
        }

        $data = [
            'email' => trim((string) Request::input('email', '')),
            'password' => (string) Request::input('password', ''),
        ];

        $validation = Validator::make(
            $data,
            [
                'email' => 'required|email',
                'password' => 'required',
            ],
            [
                'email.required' => \trans('validation.email_invalid'),
                'email.email' => \trans('validation.email_invalid'),
                'password.required' => \trans('validation.password_required'),
            ],
        );

        if ($validation->failed()) {
            Session::flash('errors', $validation->errors());
            Session::flash('old', ['email' => $data['email']]);

            return Response::redirect(UrlHelp::withQuery('/login', ['next' => $next]), 302);
        }

        $email = $data['email'];
        $password = $data['password'];

        $user = User::findByEmail($email);
        if ($user === null || ! is_string($user->password ?? null) || ! Password::verify($password, $user->password)) {
            Session::flash('errors', ['email' => \trans('auth.credentials_invalid')]);
            Session::flash('old', ['email' => $email]);

            return Response::redirect(UrlHelp::withQuery('/login', ['next' => $next]), 302);
        }

        Session::regenerate();
        Session::put('auth_user_id', (int) $user->id);

        return Response::redirect($next, 302);
    }

    /**
     * Same-origin path only: leading slash, not protocol-relative.
     */
    private static function safeNext(mixed $raw): string
    {
        if (! is_string($raw) || $raw === '') {
            return '/';
        }

        if (! str_starts_with($raw, '/') || str_starts_with($raw, '//')) {
            return '/';
        }

        return $raw;
    }
}
