<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class UserBadge extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['user_id', 'badge_id', 'awarded_at'];

    public function user(): ?User
    {
        /** @var User|null $user */
        $user = $this->belongsTo(User::class, 'user_id');

        return $user;
    }

    public function badge(): ?Badge
    {
        /** @var Badge|null $badge */
        $badge = $this->belongsTo(Badge::class, 'badge_id');

        return $badge;
    }
}
