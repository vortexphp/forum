<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Thread extends Model
{
    /** @var list<string> */
    protected static array $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'body',
        'is_locked',
        'is_pinned',
        'reply_count',
        'last_post_at',
    ];

    /**
     * @return list<string>
     */
    protected static function excludedFromUpdate(): array
    {
        return ['user_id', 'category_id'];
    }

    public static function findByCategoryAndSlug(int $categoryId, string $slug): ?self
    {
        return static::query()
            ->where('category_id', $categoryId)
            ->where('slug', trim(strtolower($slug)))
            ->first();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateForCategory(int $categoryId, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $count = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM threads WHERE category_id = ?',
            [$categoryId],
        );
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT t.*, u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role'
            . ' FROM threads t'
            . ' INNER JOIN users u ON u.id = t.user_id'
            . ' WHERE t.category_id = ?'
            . ' ORDER BY t.is_pinned DESC, t.last_post_at DESC, t.id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$categoryId],
        );

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findWithAuthor(int $threadId): ?array
    {
        return static::connection()->selectOne(
            'SELECT t.*, u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role'
            . ' FROM threads t'
            . ' INNER JOIN users u ON u.id = t.user_id'
            . ' WHERE t.id = ?'
            . ' LIMIT 1',
            [$threadId],
        );
    }

    public static function touchAfterReply(int $threadId): void
    {
        static::connection()->execute(
            'UPDATE threads'
            . ' SET reply_count = (SELECT COUNT(*) - 1 FROM posts WHERE thread_id = ?),'
            . ' last_post_at = ?,'
            . ' updated_at = ?'
            . ' WHERE id = ?',
            [$threadId, gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s'), $threadId],
        );
    }
}
