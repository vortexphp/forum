<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Category extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'slug', 'icon', 'color', 'description', 'sort_order', 'is_locked'];

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateWithStats(int $page, int $perPage = 12): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $count = static::connection()->selectOne('SELECT COUNT(*) AS n FROM categories');
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT c.*,'
            . ' COUNT(DISTINCT t.id) AS thread_total,'
            . ' COALESCE(SUM(t.reply_count), 0) AS reply_total,'
            . ' MAX(t.last_post_at) AS last_post_at'
            . ' FROM categories c'
            . ' LEFT JOIN threads t ON t.category_id = c.id'
            . ' GROUP BY c.id'
            . ' ORDER BY c.sort_order ASC, c.name ASC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        return ['items' => $items, 'total' => $total];
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', trim(strtolower($slug)))->first();
    }
}
