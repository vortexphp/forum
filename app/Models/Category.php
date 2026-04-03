<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Category extends Model
{
    protected static ?string $table = 'categories';

    /** @var list<string> */
    protected static array $fillable = ['name', 'slug', 'icon', 'color', 'description', 'sort_order', 'is_locked'];

    /**
     * @return list<Thread>
     */
    public function threads(): array
    {
        /** @var list<Thread> $threads */
        $threads = $this->hasMany(Thread::class, 'category_id');

        return $threads;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateWithStats(int $page, int $perPage = 12): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $total = static::query()->count();

        $items = static::query()
            ->select([
                'categories.*',
                'COUNT(DISTINCT t.id) AS thread_total',
                'COALESCE(SUM(t.reply_count), 0) AS reply_total',
                'MAX(t.last_post_at) AS last_post_at',
            ])
            ->leftJoin('threads AS t', 't.category_id', '=', 'categories.id')
            ->groupBy('categories.id')
            ->orderBy('categories.sort_order', 'ASC')
            ->orderBy('categories.name', 'ASC')
            ->offset($offset)
            ->limit($perPage)
            ->getRaw();

        return ['items' => $items, 'total' => $total];
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', trim(strtolower($slug)))->first();
    }
}
