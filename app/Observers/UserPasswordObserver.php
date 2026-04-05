<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use InvalidArgumentException;
use Vortex\Crypto\Password;

/**
 * Hashes plain-text passwords from admin / forms; keeps existing hash when the password field is left blank on update.
 */
final class UserPasswordObserver
{
    public function creating(User $user): void
    {
        $pwd = (string) ($user->password ?? '');
        if ($pwd === '') {
            throw new InvalidArgumentException('Password is required for new users.');
        }
        $user->password = $this->hashIfPlain($pwd);
    }

    public function updating(User $user): void
    {
        $pwd = $user->password ?? null;
        if (! is_string($pwd)) {
            return;
        }
        if ($pwd === '') {
            $fresh = User::find((int) ($user->id ?? 0));
            if ($fresh !== null) {
                $user->password = (string) $fresh->password;
            }

            return;
        }
        $user->password = $this->hashIfPlain($pwd);
    }

    private function hashIfPlain(string $password): string
    {
        if (str_starts_with($password, '$2y$')
            || str_starts_with($password, '$2a$')
            || str_starts_with($password, '$argon')) {
            return $password;
        }

        return Password::hash($password);
    }
}
