<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class User extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'email', 'password', 'avatar'];

    public static function findByEmail(string $email): ?self
    {
        $email = strtolower(trim($email));

        return static::query()->where('email', $email)->first();
    }
}
