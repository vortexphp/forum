<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Post;
use App\Models\Thread;

/**
 * Ensures every thread has an opening {@see Post} row (matches public thread creation and admin CRUD).
 */
final class ThreadOpeningPostObserver
{
    public function created(Thread $thread): void
    {
        $threadId = (int) ($thread->id ?? 0);
        if ($threadId < 1) {
            return;
        }
        if (Post::query()->where('thread_id', $threadId)->count() > 0) {
            return;
        }

        Post::create([
            'thread_id' => $threadId,
            'user_id' => (int) ($thread->user_id ?? 0),
            'body' => (string) ($thread->body ?? ''),
            'is_edited' => 0,
            'edited_at' => null,
        ]);
        Thread::touchAfterReply($threadId);
    }
}
