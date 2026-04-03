<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Tag extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'slug'];

    /**
     * @return list<Thread>
     */
    public function threads(): array
    {
        /** @var list<Thread> $threads */
        $threads = $this->belongsToMany(
            Thread::class,
            'thread_tags',
            'tag_id',
            'thread_id',
        );

        return $threads;
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::query()->where('slug', strtolower(trim($slug)))->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function forThread(int $threadId): array
    {
        $thread = Thread::find($threadId);
        if ($thread === null) {
            return [];
        }

        $tags = $thread->relatedTags();
        usort($tags, static fn (Tag $left, Tag $right): int => strcmp((string) ($left->name ?? ''), (string) ($right->name ?? '')));

        $out = [];
        foreach ($tags as $tag) {
            $out[] = get_object_vars($tag);
        }

        return $out;
    }
}
