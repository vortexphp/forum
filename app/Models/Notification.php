<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Notification extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['user_id', 'actor_id', 'type', 'title', 'body', 'url', 'read_at'];

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
        $row = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM notifications WHERE user_id = ? AND read_at IS NULL',
            [$userId],
        );

        return (int) ($row['n'] ?? 0);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateForUser(int $userId, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $count = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM notifications WHERE user_id = ?',
            [$userId],
        );
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT n.*, u.name AS actor_name, u.avatar AS actor_avatar'
            . ' FROM notifications n'
            . ' LEFT JOIN users u ON u.id = n.actor_id'
            . ' WHERE n.user_id = ?'
            . ' ORDER BY n.created_at DESC, n.id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$userId],
        );

        return ['items' => $items, 'total' => $total];
    }

    public static function markAllReadForUser(int $userId): void
    {
        $now = gmdate('Y-m-d H:i:s');
        static::connection()->execute(
            'UPDATE notifications'
            . ' SET read_at = ?, updated_at = ?'
            . ' WHERE user_id = ? AND read_at IS NULL',
            [$now, $now, $userId],
        );
    }
}
