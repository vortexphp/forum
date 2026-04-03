<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\User;
use App\Uploads\AvatarUploadSpec;
use Vortex\Crypto\Password;
use Vortex\Files\Storage;
use Vortex\Http\Csrf;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Http\Session;
use Vortex\Http\UploadedFile;
use Vortex\Validation\Rule;
use Vortex\Validation\Validator;
use Vortex\View\View;
use Throwable;

final class AccountHandler
{
    public function index(): Response
    {
        $status = Session::flash('status');

        return View::html('account.index', [
            'title' => \trans('account.title'),
            'status' => is_string($status) ? $status : null,
        ]);
    }

    public function edit(): Response
    {
        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }

        $defaults = [
            'name' => (string) ($user->name ?? ''),
            'email' => (string) ($user->email ?? ''),
        ];
        $oldFlash = Session::flash('old');
        $old = is_array($oldFlash) ? array_merge($defaults, $oldFlash) : $defaults;
        $errors = Session::flash('errors');

        return View::html('account.edit', [
            'title' => \trans('account.edit.title'),
            'errors' => is_array($errors) ? $errors : [],
            'old' => $old,
        ]);
    }

    public function update(): Response
    {
        if (! Csrf::validate()) {
            return Response::redirect('/account/edit', 302)
                ->withErrors(['_form' => \trans('auth.csrf_invalid')]);
        }

        $user = $this->currentUser();
        if ($user === null) {
            return Response::redirect('/login', 302);
        }

        $data = $this->readAccountForm();

        $validation = Validator::make(
            $data,
            [
                'name' => Rule::required(\trans('validation.name_required'))->string()->max(120),
                'email' => Rule::required(\trans('validation.email_required'))
                    ->email(\trans('validation.email_invalid'))
                    ->max(255),
                'password' => Rule::nullable()
                    ->min(8, \trans('validation.password_min'))
                    ->confirmed(\trans('validation.password_confirmed')),
            ],
        );

        if ($validation->failed()) {
            return $this->redirectAccountEdit($validation->errors(), $data);
        }

        if ($data['password'] === '' && $data['password_confirmation'] !== '') {
            return $this->redirectAccountEdit(
                ['password_confirmation' => \trans('validation.password_confirmed')],
                $data,
            );
        }

        $emailNorm = strtolower($data['email']);
        $currentEmail = strtolower((string) ($user->email ?? ''));
        if ($emailNorm !== $currentEmail && User::findByEmail($emailNorm) !== null) {
            return $this->redirectAccountEdit(['email' => \trans('auth.email_taken')], $data);
        }

        $upload = Request::file('avatar');
        $removeAvatar = Request::input('remove_avatar') === '1' || Request::input('remove_avatar') === 1;

        if ($upload !== null && $upload->hasFile() && ! $upload->isValid()) {
            return $this->redirectAccountEdit(['avatar' => \trans($upload->clientErrorMessage())], $data);
        }

        if ($removeAvatar && $upload !== null && $upload->hasFile()) {
            return $this->redirectAccountEdit(['avatar' => \trans('upload.remove_or_upload')], $data);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $emailNorm,
        ];
        if ($data['password'] !== '') {
            $payload['password'] = Password::hash($data['password']);
        }

        $uid = (int) $user->id;
        $previousAvatar = isset($user->avatar) && is_string($user->avatar) ? $user->avatar : null;

        if ($removeAvatar) {
            Storage::deletePublic($previousAvatar);
            $payload['avatar'] = null;
        } elseif ($upload !== null && $upload->hasFile()) {
            $stored = $this->tryStoreAvatar($upload, $uid, $previousAvatar);
            if ($stored['error'] !== null) {
                return $this->redirectAccountEdit(['avatar' => $stored['error']], $data);
            }
            $payload['avatar'] = $stored['relative'];
        }

        User::updateRecord($uid, $payload);

        return Response::redirect('/account', 302)
            ->with('status', \trans('account.edit.saved'));
    }

    /**
     * @return array{name: string, email: string, password: string, password_confirmation: string}
     */
    private function readAccountForm(): array
    {
        return [
            'name' => trim((string) Request::input('name', '')),
            'email' => trim((string) Request::input('email', '')),
            'password' => (string) Request::input('password', ''),
            'password_confirmation' => (string) Request::input('password_confirmation', ''),
        ];
    }

    /**
     * @param array<string, mixed> $errors
     * @param array{name: string, email: string, password: string, password_confirmation: string} $data
     */
    private function redirectAccountEdit(array $errors, array $data): Response
    {
        return Response::redirect('/account/edit', 302)
            ->withErrors($errors)
            ->withInput(['name' => $data['name'], 'email' => $data['email']]);
    }

    private function currentUser(): ?User
    {
        $id = Session::authUserId();

        return $id === null ? null : User::find($id);
    }

    /**
     * @return array{error: ?string, relative: ?string}
     */
    private function tryStoreAvatar(UploadedFile $upload, int $uid, ?string $previousAvatar): array
    {
        $spec = AvatarUploadSpec::fromConfig();
        $stem = $uid . '-' . bin2hex(random_bytes(8));

        try {
            $relative = Storage::storeUpload(
                $upload,
                $spec->directory,
                $stem,
                $spec->mimeExtensions,
                $spec->maxBytes,
            );
            Storage::deletePublic($previousAvatar);

            return ['error' => null, 'relative' => $relative];
        } catch (Throwable $e) {
            $key = $e->getMessage();
            $msg = str_starts_with($key, 'upload.') ? \trans($key) : \trans('upload.failed');

            return ['error' => $msg, 'relative' => null];
        }
    }
}
