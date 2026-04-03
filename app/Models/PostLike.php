<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class PostLike extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['post_id', 'user_id'];

    public static function toggle(int $postId, int $userId): bool
    {
        $existing = static::query()->where('post_id', $postId)->where('user_id', $userId)->first();
        if ($existing !== null) {
            static::deleteId((int) $existing->id);

            return false;
        }

        static::create(['post_id' => $postId, 'user_id' => $userId]);

        return true;
    }

    public static function countForPost(int $postId): int
    {
        $row = static::connection()->selectOne('SELECT COUNT(*) AS n FROM post_likes WHERE post_id = ?', [$postId]);

        return (int) ($row['n'] ?? 0);
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

        $in = implode(', ', array_fill(0, count($postIds), '?'));
        $rows = static::connection()->select(
            'SELECT post_id FROM post_likes WHERE user_id = ? AND post_id IN (' . $in . ')',
            array_merge([$userId], $postIds),
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) ($row['post_id'] ?? 0)] = true;
        }

        return $out;
    }
}
