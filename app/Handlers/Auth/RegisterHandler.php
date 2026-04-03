<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Models\User;
use Vortex\Crypto\Password;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Validation\Rule;
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
            return Response::redirect('/register', 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')])
                ->withInput(self::oldPublicFields($data));
        }

        $validation = Validator::make(
            $data,
            [
                'name' => Rule::required(\trans('validation.name_required'))->string()->max(120),
                'email' => Rule::required(\trans('validation.email_required'))
                    ->email(\trans('validation.email_invalid'))
                    ->max(255),
                'password' => Rule::required()
                    ->min(8, \trans('validation.password_min'))
                    ->confirmed(\trans('validation.password_confirmed')),
            ],
        );

        if ($validation->failed()) {
            return Response::redirect('/register', 302)
                ->withErrors($validation->errors())
                ->withInput(self::oldPublicFields($data));
        }

        $name = $data['name'];
        $email = $data['email'];
        $password = $data['password'];

        if (User::findByEmail($email) !== null) {
            return Response::redirect('/register', 302)
                ->withErrors(['email' => \trans('auth.email_taken')])
                ->withInput(self::oldPublicFields($data));
        }

        User::create([
            'name' => $name,
            'email' => strtolower($email),
            'password' => Password::hash($password),
        ]);

        return Response::redirect('/login', 302)
            ->with('status', \trans('auth.register_success_flash'));
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
