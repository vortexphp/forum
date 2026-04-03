<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\ForumBadgeService;
use Vortex\Database\Model;

final class User extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['name', 'email', 'password', 'avatar', 'role'];

    /**
     * @return list<Thread>
     */
    public function threads(): array
    {
        /** @var list<Thread> $threads */
        $threads = $this->hasMany(Thread::class, 'user_id');

        return $threads;
    }

    /**
     * @return list<Post>
     */
    public function posts(): array
    {
        /** @var list<Post> $posts */
        $posts = $this->hasMany(Post::class, 'user_id');

        return $posts;
    }

    /**
     * @return list<UserBadge>
     */
    public function badgeAwards(): array
    {
        /** @var list<UserBadge> $awards */
        $awards = $this->hasMany(UserBadge::class, 'user_id');

        return $awards;
    }

    /**
     * @return list<Badge>
     */
    public function badges(): array
    {
        /** @var list<Badge> $badges */
        $badges = $this->belongsToMany(
            Badge::class,
            'user_badges',
            'user_id',
            'badge_id',
        );

        return $badges;
    }

    /**
     * @return list<ThreadBookmark>
     */
    public function bookmarks(): array
    {
        /** @var list<ThreadBookmark> $bookmarks */
        $bookmarks = $this->hasMany(ThreadBookmark::class, 'user_id');

        return $bookmarks;
    }

    /**
     * @return list<PostLike>
     */
    public function likes(): array
    {
        /** @var list<PostLike> $likes */
        $likes = $this->hasMany(PostLike::class, 'user_id');

        return $likes;
    }

    /**
     * @return list<Notification>
     */
    public function notifications(): array
    {
        /** @var list<Notification> $notifications */
        $notifications = $this->hasMany(Notification::class, 'user_id');

        return $notifications;
    }

    /**
     * @return list<Notification>
     */
    public function actorNotifications(): array
    {
        /** @var list<Notification> $notifications */
        $notifications = $this->hasMany(Notification::class, 'actor_id');

        return $notifications;
    }

    public static function findByEmail(string $email): ?self
    {
        $email = strtolower(trim($email));

        return static::query()->where('email', $email)->first();
    }

    public static function findByName(string $name): ?self
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return static::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();
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
        $threadsCount = Thread::query()->where('user_id', $userId)->count();
        $postsCount = Post::query()->where('user_id', $userId)->count();

        /** @var list<int> $threadIds */
        $threadIds = array_map(
            static fn (mixed $id): int => (int) $id,
            Thread::query()->where('user_id', $userId)->pluck('id'),
        );
        /** @var list<int> $postIds */
        $postIds = array_map(
            static fn (mixed $id): int => (int) $id,
            Post::query()->where('user_id', $userId)->pluck('id'),
        );

        $likesReceived = $postIds === []
            ? 0
            : PostLike::query()->whereIn('post_id', $postIds)->count();

        $likesGiven = PostLike::query()->where('user_id', $userId)->count();

        $threadFlags = $threadIds === []
            ? 0
            : ContentFlag::query()
                ->where('target_type', 'thread')
                ->whereIn('target_id', $threadIds)
                ->count();
        $postFlags = $postIds === []
            ? 0
            : ContentFlag::query()
                ->where('target_type', 'post')
                ->whereIn('target_id', $postIds)
                ->count();

        return [
            'threads_count' => $threadsCount,
            'posts_count' => $postsCount,
            'likes_received' => $likesReceived,
            'likes_given' => $likesGiven,
            'flags_received' => $threadFlags + $postFlags,
        ];
    }

    /**
     * @return list<string>
     */
    public function publicBadges(): array
    {
        ForumBadgeService::recalculateForUser((int) ($this->id ?? 0));

        $userId = (int) ($this->id ?? 0);
        $awards = UserBadge::query()
            ->where('user_id', $userId)
            ->orderBy('awarded_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $badgeIds = [];
        foreach ($awards as $award) {
            $badgeId = (int) ($award->badge_id ?? 0);
            if ($badgeId > 0) {
                $badgeIds[] = $badgeId;
            }
        }

        $badgesById = [];
        if ($badgeIds !== []) {
            $badges = Badge::query()->whereIn('id', $badgeIds)->get();
            foreach ($badges as $badge) {
                $badgeId = (int) ($badge->id ?? 0);
                if ($badgeId > 0) {
                    $badgesById[$badgeId] = $badge;
                }
            }
        }

        usort($awards, static function (Model $left, Model $right) use ($badgesById): int {
            $leftBadge = $badgesById[(int) ($left->badge_id ?? 0)] ?? null;
            $rightBadge = $badgesById[(int) ($right->badge_id ?? 0)] ?? null;

            $leftSort = (int) ($leftBadge?->sort_order ?? 1000);
            $rightSort = (int) ($rightBadge?->sort_order ?? 1000);
            if ($leftSort !== $rightSort) {
                return $leftSort <=> $rightSort;
            }

            $leftAwardedAt = (string) ($left->awarded_at ?? '');
            $rightAwardedAt = (string) ($right->awarded_at ?? '');
            if ($leftAwardedAt !== $rightAwardedAt) {
                return $leftAwardedAt <=> $rightAwardedAt;
            }

            return (int) ($left->id ?? 0) <=> (int) ($right->id ?? 0);
        });

        $badges = [];
        foreach ($awards as $award) {
            $badge = $badgesById[(int) ($award->badge_id ?? 0)] ?? null;
            $key = (string) ($badge?->badge_key ?? '');
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
