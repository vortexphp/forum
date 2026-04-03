<?php

declare(strict_types=1);

namespace App\Models;

use PDOException;
use Vortex\Database\Model;

final class ThreadBookmark extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['thread_id', 'user_id'];

    public function thread(): ?Thread
    {
        /** @var Thread|null $thread */
        $thread = $this->belongsTo(Thread::class, 'thread_id');

        return $thread;
    }

    public function user(): ?User
    {
        /** @var User|null $user */
        $user = $this->belongsTo(User::class, 'user_id');

        return $user;
    }

    public static function hasForUser(int $threadId, int $userId): bool
    {
        return static::query()
            ->where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function toggle(int $threadId, int $userId): bool
    {
        $deleted = static::connection()->execute(
            'DELETE FROM thread_bookmarks WHERE thread_id = ? AND user_id = ?',
            [$threadId, $userId],
        );
        if ($deleted > 0) {
            return false;
        }

        try {
            static::create(['thread_id' => $threadId, 'user_id' => $userId]);
        } catch (PDOException $e) {
            if (static::query()->where('thread_id', $threadId)->where('user_id', $userId)->exists()) {
                return true;
            }
            throw $e;
        }

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

        $total = static::query()->where('user_id', $userId)->count();
        $items = static::query()
            ->select([
                't.*',
                'c.slug AS category_slug',
                'c.name AS category_name',
                'u.name AS author_name',
                'u.avatar AS author_avatar',
                'u.role AS author_role',
            ])
            ->join('threads AS t', 't.id', '=', 'thread_bookmarks.thread_id')
            ->join('categories AS c', 'c.id', '=', 't.category_id')
            ->join('users AS u', 'u.id', '=', 't.user_id')
            ->where('thread_bookmarks.user_id', $userId)
            ->orderBy('thread_bookmarks.created_at', 'DESC')
            ->orderBy('thread_bookmarks.id', 'DESC')
            ->offset($offset)
            ->limit($perPage)
            ->getRaw();

        return ['items' => $items, 'total' => $total];
    }
}
