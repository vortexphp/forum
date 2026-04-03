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
use Vortex\Validation\Rule;
use Vortex\Validation\Validator;
use Vortex\View\View;

final class LoginHandler
{
    public function show(): Response
    {
        $session = Session::store();
        $errors = $session->flashGet('errors');
        $status = $session->flashGet('status');
        $installAdminEmail = $session->flashGet('install_admin_email');
        if (is_string($installAdminEmail) && $installAdminEmail !== '') {
            $status = 'Installation completed. Admin login: ' . $installAdminEmail;
        }
        $old = $session->flashGet('old');
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
        $session = Session::store();
        $next = self::safeNext(Request::input('next', '/'));

        if (! Csrf::validate()) {
            return Response::redirect(UrlHelp::withQuery('/login', ['next' => $next]), 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')]);
        }

        $data = [
            'email' => trim((string) Request::input('email', '')),
            'password' => (string) Request::input('password', ''),
        ];

        $validation = Validator::make(
            $data,
            [
                'email' => Rule::required(\trans('validation.email_invalid'))
                    ->email(\trans('validation.email_invalid')),
                'password' => Rule::required(\trans('validation.password_required')),
            ],
        );

        if ($validation->failed()) {
            return Response::redirect(UrlHelp::withQuery('/login', ['next' => $next]), 302)
                ->withErrors($validation->errors())
                ->withInput(['email' => $data['email']]);
        }

        $email = $data['email'];
        $password = $data['password'];

        $user = str_contains($email, '@')
            ? User::findByEmail($email)
            : User::findByName($email);
        if ($user === null || ! is_string($user->password ?? null) || ! Password::verify($password, $user->password)) {
            return Response::redirect(UrlHelp::withQuery('/login', ['next' => $next]), 302)
                ->withErrors(['email' => \trans('auth.credentials_invalid')])
                ->withInput(['email' => $email]);
        }

        $session->regenerate();
        $session->put('auth_user_id', (int) $user->id);

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
