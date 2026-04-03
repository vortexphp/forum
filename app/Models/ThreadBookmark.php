<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class ThreadBookmark extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['thread_id', 'user_id'];

    public static function hasForUser(int $threadId, int $userId): bool
    {
        $row = static::query()
            ->where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->first();

        return $row !== null;
    }

    public static function toggle(int $threadId, int $userId): bool
    {
        $existing = static::query()
            ->where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->first();

        if ($existing !== null) {
            static::deleteId((int) $existing->id);

            return false;
        }

        static::create(['thread_id' => $threadId, 'user_id' => $userId]);

        return true;
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
            'SELECT COUNT(*) AS n FROM thread_bookmarks WHERE user_id = ?',
            [$userId],
        );
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT t.*, c.slug AS category_slug, c.name AS category_name,'
            . ' u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role'
            . ' FROM thread_bookmarks tb'
            . ' INNER JOIN threads t ON t.id = tb.thread_id'
            . ' INNER JOIN categories c ON c.id = t.category_id'
            . ' INNER JOIN users u ON u.id = t.user_id'
            . ' WHERE tb.user_id = ?'
            . ' ORDER BY tb.created_at DESC, tb.id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$userId],
        );

        return ['items' => $items, 'total' => $total];
    }
}
