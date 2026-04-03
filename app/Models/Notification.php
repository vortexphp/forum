<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Notification extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['user_id', 'actor_id', 'type', 'title', 'body', 'url', 'read_at'];

    public function user(): ?User
    {
        /** @var User|null $user */
        $user = $this->belongsTo(User::class, 'user_id');

        return $user;
    }

    public function actor(): ?User
    {
        /** @var User|null $actor */
        $actor = $this->belongsTo(User::class, 'actor_id');

        return $actor;
    }

    public static function createForUser(
        int $userId,
        ?int $actorId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
    ): void {
        static::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'read_at' => null,
        ]);
    }

    public static function unreadCountForUser(int $userId): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateForUser(int $userId, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $total = static::query()->where('user_id', $userId)->count();

        $items = static::query()
            ->select([
                'notifications.*',
                'u.name AS actor_name',
                'u.avatar AS actor_avatar',
            ])
            ->leftJoin('users AS u', 'u.id', '=', 'notifications.actor_id')
            ->where('notifications.user_id', $userId)
            ->orderBy('notifications.created_at', 'DESC')
            ->orderBy('notifications.id', 'DESC')
            ->offset($offset)
            ->limit($perPage)
            ->getRaw();

        return ['items' => $items, 'total' => $total];
    }

    public static function markAllReadForUser(int $userId): void
    {
        $now = gmdate('Y-m-d H:i:s');
        static::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => $now,
                'updated_at' => $now,
            ]);
    }
}
