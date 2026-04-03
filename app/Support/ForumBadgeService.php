<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class ForumBadgeService
{
    /**
     * @var list<array{badge_key:string, sort_order:int, kind:string, threshold:int}>
     */
    private const RULES = [
        ['badge_key' => 'first_thread', 'sort_order' => 10, 'kind' => 'threads', 'threshold' => 1],
        ['badge_key' => 'first_reply', 'sort_order' => 20, 'kind' => 'posts', 'threshold' => 2],
        ['badge_key' => 'thread_starter', 'sort_order' => 30, 'kind' => 'threads', 'threshold' => 10],
        ['badge_key' => 'active_member', 'sort_order' => 40, 'kind' => 'posts', 'threshold' => 25],
        ['badge_key' => 'community_voice', 'sort_order' => 50, 'kind' => 'posts', 'threshold' => 100],
        ['badge_key' => 'liked_author', 'sort_order' => 60, 'kind' => 'likes_received', 'threshold' => 25],
        ['badge_key' => 'appreciated_author', 'sort_order' => 70, 'kind' => 'likes_received', 'threshold' => 100],
        ['badge_key' => 'supporter', 'sort_order' => 80, 'kind' => 'likes_given', 'threshold' => 25],
        ['badge_key' => 'veteran', 'sort_order' => 90, 'kind' => 'days', 'threshold' => 30],
    ];

    public static function recalculateForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        self::ensureCatalog();

        $user = User::find($userId);
        if ($user === null) {
            return;
        }

        $stats = User::publicStats($userId);
        $days = self::daysSince((string) ($user->created_at ?? ''));
        $db = User::connection();

        foreach (self::RULES as $rule) {
            $current = match ($rule['kind']) {
                'threads' => $stats['threads_count'],
                'posts' => $stats['posts_count'],
                'likes_received' => $stats['likes_received'],
                'likes_given' => $stats['likes_given'],
                'days' => $days,
                default => 0,
            };

            if ($current < $rule['threshold']) {
                continue;
            }

            $badgeRow = $db->selectOne('SELECT id FROM badges WHERE badge_key = ? LIMIT 1', [$rule['badge_key']]);
            if ($badgeRow === null) {
                continue;
            }
            $badgeId = (int) ($badgeRow['id'] ?? 0);
            if ($badgeId <= 0) {
                continue;
            }

            $exists = $db->selectOne(
                'SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ? LIMIT 1',
                [$userId, $badgeId],
            );
            if ($exists !== null) {
                continue;
            }

            $now = gmdate('Y-m-d H:i:s');
            $db->execute(
                'INSERT INTO user_badges (user_id, badge_id, awarded_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$userId, $badgeId, $now, $now, $now],
            );
        }
    }

    private static function ensureCatalog(): void
    {
        $db = User::connection();
        foreach (self::RULES as $rule) {
            $existing = $db->selectOne('SELECT id FROM badges WHERE badge_key = ? LIMIT 1', [$rule['badge_key']]);
            if ($existing !== null) {
                continue;
            }
            $now = gmdate('Y-m-d H:i:s');
            $db->execute(
                'INSERT INTO badges (badge_key, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?)',
                [$rule['badge_key'], $rule['sort_order'], $now, $now],
            );
        }
    }

    private static function daysSince(string $createdAt): int
    {
        $ts = strtotime($createdAt);
        if ($ts === false) {
            return 0;
        }

        return max(0, (int) floor((time() - $ts) / 86400));
    }
}
