<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Badge extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['badge_key', 'sort_order'];

    /**
     * @return list<UserBadge>
     */
    public function awards(): array
    {
        /** @var list<UserBadge> $awards */
        $awards = $this->hasMany(UserBadge::class, 'badge_id');

        return $awards;
    }

    /**
     * @return list<User>
     */
    public function users(): array
    {
        /** @var list<User> $users */
        $users = $this->belongsToMany(
            User::class,
            'user_badges',
            'badge_id',
            'user_id',
        );

        return $users;
    }
}
