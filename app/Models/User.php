<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ForumBadgeService;
use Vortex\Database\Model;

final class User extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'email', 'password', 'avatar', 'role'];

    public static function findByEmail(string $email): ?self
    {
        $email = strtolower(trim($email));

        return static::query()->where('email', $email)->first();
    }

    public function isModerator(): bool
    {
        return strtolower((string) ($this->role ?? 'member')) === 'moderator';
    }

    /**
     * @return array{
     *   threads_count:int,
     *   posts_count:int,
     *   likes_received:int,
     *   likes_given:int,
     *   flags_received:int
     * }
     */
    public static function publicStats(int $userId): array
    {
        $threads = static::connection()->selectOne('SELECT COUNT(*) AS n FROM threads WHERE user_id = ?', [$userId]);
        $posts = static::connection()->selectOne('SELECT COUNT(*) AS n FROM posts WHERE user_id = ?', [$userId]);
        $likesReceived = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM post_likes pl INNER JOIN posts p ON p.id = pl.post_id WHERE p.user_id = ?',
            [$userId],
        );
        $likesGiven = static::connection()->selectOne('SELECT COUNT(*) AS n FROM post_likes WHERE user_id = ?', [$userId]);
        $flagsReceived = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM content_flags cf WHERE '
            . "(cf.target_type = 'thread' AND cf.target_id IN (SELECT t.id FROM threads t WHERE t.user_id = ?))"
            . " OR (cf.target_type = 'post' AND cf.target_id IN (SELECT p.id FROM posts p WHERE p.user_id = ?))",
            [$userId, $userId],
        );

        return [
            'threads_count' => (int) ($threads['n'] ?? 0),
            'posts_count' => (int) ($posts['n'] ?? 0),
            'likes_received' => (int) ($likesReceived['n'] ?? 0),
            'likes_given' => (int) ($likesGiven['n'] ?? 0),
            'flags_received' => (int) ($flagsReceived['n'] ?? 0),
        ];
    }

    /**
     * @return list<string>
     */
    public function publicBadges(): array
    {
        ForumBadgeService::recalculateForUser((int) ($this->id ?? 0));

        $rows = static::connection()->select(
            'SELECT b.badge_key FROM user_badges ub'
            . ' INNER JOIN badges b ON b.id = ub.badge_id'
            . ' WHERE ub.user_id = ?'
            . ' ORDER BY b.sort_order ASC, ub.awarded_at ASC',
            [(int) ($this->id ?? 0)],
        );

        $badges = [];
        foreach ($rows as $row) {
            $key = (string) ($row['badge_key'] ?? '');
            if ($key !== '') {
                $badges[] = $key;
            }
        }
        if ($this->isModerator()) {
            $badges[] = 'moderator';
        }

        return $badges;
    }
}
