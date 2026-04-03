<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Category extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'slug', 'icon', 'color', 'description', 'sort_order', 'is_locked'];

    /**
     * @return list<array<string, mixed>>
     */
    public static function listWithStats(): array
    {
        return static::connection()->select(
            'SELECT c.*,'
            . ' COUNT(DISTINCT t.id) AS thread_total,'
            . ' COALESCE(SUM(t.reply_count), 0) AS reply_total,'
            . ' MAX(t.last_post_at) AS last_post_at'
            . ' FROM categories c'
            . ' LEFT JOIN threads t ON t.category_id = c.id'
            . ' GROUP BY c.id'
            . ' ORDER BY c.sort_order ASC, c.name ASC'
        );
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', trim(strtolower($slug)))->first();
    }
}
