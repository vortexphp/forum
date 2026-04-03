<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Models\User;
use Vortex\Crypto\Password;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Validation\Validator;
use Vortex\View\View;

final class RegisterHandler
{
    public function show(): Response
    {
        $errors = Session::flash('errors');
        $old = Session::flash('old');

        return View::html('auth.register', [
            'title' => \trans('auth.register.title'),
            'errors' => is_array($errors) ? $errors : [],
            'old' => is_array($old) ? $old : [],
        ]);
    }

    public function store(): Response
    {
        $data = [
            'name' => trim((string) Request::input('name', '')),
            'email' => trim((string) Request::input('email', '')),
            'password' => (string) Request::input('password', ''),
            'password_confirmation' => (string) Request::input('password_confirmation', ''),
        ];

        if (! Csrf::validate()) {
            Session::flash('errors', ['_form' => \trans('auth.csrf_invalid')]);
            Session::flash('old', self::oldPublicFields($data));

            return Response::redirect('/register', 302);
        }

        $validation = Validator::make(
            $data,
            [
                'name' => 'required|string|max:120',
                'email' => 'required|email|max:255',
                'password' => 'required|min:8|confirmed',
            ],
            [
                'name.required' => \trans('validation.name_required'),
                'email.required' => \trans('validation.email_required'),
                'email.email' => \trans('validation.email_invalid'),
                'password.min' => \trans('validation.password_min'),
                'password.confirmed' => \trans('validation.password_confirmed'),
            ],
        );

        if ($validation->failed()) {
            Session::flash('errors', $validation->errors());
            Session::flash('old', self::oldPublicFields($data));

            return Response::redirect('/register', 302);
        }

        $name = $data['name'];
        $email = $data['email'];
        $password = $data['password'];

        if (User::findByEmail($email) !== null) {
            Session::flash('errors', ['email' => \trans('auth.email_taken')]);
            Session::flash('old', self::oldPublicFields($data));

            return Response::redirect('/register', 302);
        }

        User::create([
            'name' => $name,
            'email' => strtolower($email),
            'password' => Password::hash($password),
        ]);

        Session::flash('status', \trans('auth.register_success_flash'));

        return Response::redirect('/login', 302);
    }

    /**
     * @param array{name: string, email: string, password: string, password_confirmation: string} $data
     *
     * @return array{name: string, email: string}
     */
    private static function oldPublicFields(array $data): array
    {
        return [
            'name' => $data['name'],
            'email' => $data['email'],
        ];
    }
}
