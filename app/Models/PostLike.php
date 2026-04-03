<?php

declare(strict_types=1);

namespace App\Models;

use PDOException;
use Vortex\Database\Model;

final class PostLike extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['post_id', 'user_id'];

    public function post(): ?Post
    {
        /** @var Post|null $post */
        $post = $this->belongsTo(Post::class, 'post_id');

        return $post;
    }

    public function user(): ?User
    {
        /** @var User|null $user */
        $user = $this->belongsTo(User::class, 'user_id');

        return $user;
    }

    public static function toggle(int $postId, int $userId): bool
    {
        $deleted = static::connection()->execute(
            'DELETE FROM post_likes WHERE post_id = ? AND user_id = ?',
            [$postId, $userId],
        );
        if ($deleted > 0) {
            return false;
        }

        try {
            static::create(['post_id' => $postId, 'user_id' => $userId]);
        } catch (PDOException $e) {
            // If another request inserted concurrently, treat current state as liked.
            if (static::query()->where('post_id', $postId)->where('user_id', $userId)->exists()) {
                return true;
            }
            throw $e;
        }

        return true;
    }

    public static function countForPost(int $postId): int
    {
        return static::query()->where('post_id', $postId)->count();
    }

    /**
     * @param list<int> $postIds
     * @return array<int, true>
     */
    public static function likedMapForUser(int $userId, array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }

        $rows = static::query()
            ->select(['post_id'])
            ->where('user_id', $userId)
            ->whereIn('post_id', $postIds)
            ->getRaw();
        $out = [];
        foreach ($rows as $row) {
            $out[(int) ($row['post_id'] ?? 0)] = true;
        }

        return $out;
    }
}
