<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\ForumSetting;
use App\Models\User;
use App\Support\ForumSeedService;
use App\Support\InstallState;
use Vortex\Crypto\Password;
use Throwable;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\View\View;

final class InstallHandler
{
    public function show(): Response
    {
        if (InstallState::isInstalled()) {
            return Response::redirect('/', 302);
        }

        $status = Session::flash('status');
        $errors = Session::flash('errors');
        $old = Session::flash('old');
        $defaults = [
            'forum_title' => 'VortexForum',
            'forum_description' => 'Discuss. Share. Build together.',
            'admin_username' => 'admin',
            'include_default_content' => '1',
        ];

        return View::html('install.index', [
            'title' => 'Install Forum',
            'status' => is_string($status) ? $status : null,
            'errors' => is_array($errors) ? $errors : [],
            'old' => is_array($old) ? array_merge($defaults, $old) : $defaults,
        ]);
    }

    public function run(): Response
    {
        if (InstallState::isInstalled()) {
            return Response::redirect('/', 302);
        }

        if (! Csrf::validate()) {
            return Response::redirect('/install', 302)
                ->withErrors(['_form' => 'Invalid CSRF token. Please reload and try again.'])
                ->withInput($this->readInstallForm());
        }

        $data = $this->readInstallForm();
        $errors = $this->validateInstallForm($data);
        if ($errors !== []) {
            return Response::redirect('/install', 302)
                ->withErrors($errors)
                ->withInput($data);
        }

        try {
            InstallState::install();

            ForumSetting::setValue('forum_title', $data['forum_title']);
            ForumSetting::setValue('forum_description', $data['forum_description']);

            $username = $data['admin_username'];
            $adminEmail = $this->resolveAdminEmail($username);
            $admin = User::findByEmail($adminEmail);
            if ($admin === null) {
                User::create([
                    'name' => $username,
                    'email' => $adminEmail,
                    'password' => Password::hash($data['admin_password']),
                    'avatar' => null,
                    'role' => 'moderator',
                ]);
            }

            if ($data['include_default_content']) {
                (new ForumSeedService())->seed();
            }

            return Response::redirect('/login', 302)
                ->withMany([
                    'status' => 'Installation completed. You can now sign in.',
                    'install_admin_email' => $adminEmail,
                ]);
        } catch (Throwable $e) {
            return Response::redirect('/install', 302)
                ->withErrors(['_form' => 'Installation failed: ' . $e->getMessage()])
                ->withInput($data);
        }
    }

    /**
     * @return array{
     *   forum_title:string,
     *   forum_description:string,
     *   admin_username:string,
     *   admin_password:string,
     *   admin_password_confirmation:string,
     *   include_default_content:bool
     * }
     */
    private function readInstallForm(): array
    {
        return [
            'forum_title' => trim((string) Request::input('forum_title', '')),
            'forum_description' => trim((string) Request::input('forum_description', '')),
            'admin_username' => trim((string) Request::input('admin_username', '')),
            'admin_password' => (string) Request::input('admin_password', ''),
            'admin_password_confirmation' => (string) Request::input('admin_password_confirmation', ''),
            'include_default_content' => Request::input('include_default_content') === '1' || Request::input('include_default_content') === 1,
        ];
    }

    /**
     * @param array{
     *   forum_title:string,
     *   forum_description:string,
     *   admin_username:string,
     *   admin_password:string,
     *   admin_password_confirmation:string,
     *   include_default_content:bool
     * } $data
     * @return array<string, string>
     */
    private function validateInstallForm(array $data): array
    {
        $errors = [];

        if ($data['forum_title'] === '') {
            $errors['forum_title'] = 'Forum title is required.';
        } elseif (mb_strlen($data['forum_title']) > 120) {
            $errors['forum_title'] = 'Forum title must be at most 120 characters.';
        }

        if ($data['forum_description'] === '') {
            $errors['forum_description'] = 'Forum description is required.';
        } elseif (mb_strlen($data['forum_description']) > 500) {
            $errors['forum_description'] = 'Forum description must be at most 500 characters.';
        }

        if ($data['admin_username'] === '') {
            $errors['admin_username'] = 'Admin username is required.';
        } elseif (mb_strlen($data['admin_username']) > 120) {
            $errors['admin_username'] = 'Admin username must be at most 120 characters.';
        }

        if ($data['admin_password'] === '') {
            $errors['admin_password'] = 'Admin password is required.';
        } elseif (mb_strlen($data['admin_password']) < 8) {
            $errors['admin_password'] = 'Admin password must be at least 8 characters.';
        }

        if ($data['admin_password'] !== $data['admin_password_confirmation']) {
            $errors['admin_password_confirmation'] = 'Password confirmation does not match.';
        }

        return $errors;
    }

    private function resolveAdminEmail(string $username): string
    {
        $base = strtolower(trim($username));
        $base = preg_replace('/[^a-z0-9]+/', '.', $base) ?? '';
        $base = trim($base, '.');
        if ($base === '') {
            $base = 'admin';
        }

        $candidate = $base . '@forum.local';
        $idx = 1;
        while (User::findByEmail($candidate) !== null) {
            ++$idx;
            $candidate = $base . '+' . $idx . '@forum.local';
        }

        return $candidate;
    }
}
