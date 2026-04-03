<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Post extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['thread_id', 'user_id', 'body', 'is_edited', 'edited_at'];

    /**
     * @return list<string>
     */
    protected static function excludedFromUpdate(): array
    {
        return ['thread_id', 'user_id'];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateForThread(int $threadId, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $count = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM posts WHERE thread_id = ?',
            [$threadId],
        );
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT p.*, u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role,'
            . ' COALESCE(COUNT(pl.id), 0) AS likes_count'
            . ' FROM posts p'
            . ' INNER JOIN users u ON u.id = p.user_id'
            . ' LEFT JOIN post_likes pl ON pl.post_id = p.id'
            . ' WHERE p.thread_id = ?'
            . ' GROUP BY p.id'
            . ' ORDER BY p.created_at ASC, p.id ASC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$threadId],
        );

        return ['items' => $items, 'total' => $total];
    }

    public static function findInThread(int $postId, int $threadId): ?self
    {
        return static::query()
            ->where('id', $postId)
            ->where('thread_id', $threadId)
            ->first();
    }
}
