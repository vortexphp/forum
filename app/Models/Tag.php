<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Tag extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'slug'];

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', strtolower(trim($slug)))->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forThread(int $threadId): array
    {
        return static::connection()->select(
            'SELECT t.* FROM tags t'
            . ' INNER JOIN thread_tags tt ON tt.tag_id = t.id'
            . ' WHERE tt.thread_id = ?'
            . ' ORDER BY t.name ASC',
            [$threadId],
        );
    }
}
