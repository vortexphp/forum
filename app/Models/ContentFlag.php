<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class ContentFlag extends Model
{
    /** @var list<string> */
    protected static array $fillable = ['reporter_id', 'target_type', 'target_id', 'reason', 'status'];

    public function reporter(): ?User
    {
        /** @var User|null $reporter */
        $reporter = $this->belongsTo(User::class, 'reporter_id');

        return $reporter;
    }

    public function threadTarget(): ?Thread
    {
        if ((string) ($this->target_type ?? '') !== 'thread') {
            return null;
        }

        /** @var Thread|null $thread */
        $thread = Thread::find((int) ($this->target_id ?? 0));

        return $thread;
    }

    public function postTarget(): ?Post
    {
        if ((string) ($this->target_type ?? '') !== 'post') {
            return null;
        }

        /** @var Post|null $post */
        $post = Post::find((int) ($this->target_id ?? 0));

        return $post;
    }

    public static function hasUserFlagged(int $userId, string $targetType, int $targetId): bool
    {
        return static::query()
            ->where('reporter_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->exists();
    }
}
