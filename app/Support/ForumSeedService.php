<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Badge;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserBadge;
use Vortex\Crypto\Password;

final class ForumSeedService
{
    /**
     * @return array{categories:int, users:int, threads:int, posts:int, likes:int}
     */
    public function seed(): array
    {
        $categoryRows = [
            ['name' => 'General', 'slug' => 'general', 'icon' => '💬', 'color' => '#10b981', 'description' => 'General community discussion.', 'sort_order' => 1],
            ['name' => 'Announcements', 'slug' => 'announcements', 'icon' => '📢', 'color' => '#f59e0b', 'description' => 'Official project updates.', 'sort_order' => 2],
            ['name' => 'Support', 'slug' => 'support', 'icon' => '🛟', 'color' => '#3b82f6', 'description' => 'Ask questions and get help.', 'sort_order' => 3],
            ['name' => 'Showcase', 'slug' => 'showcase', 'icon' => '✨', 'color' => '#8b5cf6', 'description' => 'Share what you are building.', 'sort_order' => 4],
            ['name' => 'Ideas', 'slug' => 'ideas', 'icon' => '💡', 'color' => '#ec4899', 'description' => 'Feature requests and product ideas.', 'sort_order' => 5],
            ['name' => 'Off Topic', 'slug' => 'off-topic', 'icon' => '☕', 'color' => '#6b7280', 'description' => 'Everything outside the project scope.', 'sort_order' => 6],
        ];

        $createdCategories = 0;
        foreach ($categoryRows as $row) {
            if (Category::findBySlug($row['slug']) !== null) {
                continue;
            }

            Category::create([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'icon' => $row['icon'],
                'color' => $row['color'],
                'description' => $row['description'],
                'sort_order' => $row['sort_order'],
                'is_locked' => 0,
            ]);
            ++$createdCategories;
        }

        $userRows = [
            ['name' => 'Alex Admin', 'email' => 'alex.admin@example.com', 'password' => 'password123', 'avatar' => null, 'role' => 'moderator'],
            ['name' => 'Maya Maker', 'email' => 'maya.maker@example.com', 'password' => 'password123', 'avatar' => null, 'role' => 'member'],
            ['name' => 'Sam Support', 'email' => 'sam.support@example.com', 'password' => 'password123', 'avatar' => null, 'role' => 'member'],
            ['name' => 'Nina Newbie', 'email' => 'nina.newbie@example.com', 'password' => 'password123', 'avatar' => null, 'role' => 'member'],
        ];

        $usersByEmail = [];
        $createdUsers = 0;
        foreach ($userRows as $row) {
            $existing = User::findByEmail($row['email']);
            if ($existing === null) {
                $existing = User::create([
                    'name' => $row['name'],
                    'email' => strtolower($row['email']),
                    'password' => Password::hash($row['password']),
                    'avatar' => $row['avatar'],
                    'role' => $row['role'],
                ]);
                ++$createdUsers;
            } else {
                User::updateRecord((int) $existing->id, [
                    'name' => $row['name'],
                    'avatar' => $row['avatar'],
                    'role' => $row['role'],
                ]);
                $existing = User::find((int) $existing->id);
            }

            if ($existing !== null) {
                $usersByEmail[strtolower($row['email'])] = $existing;
            }
        }

        $categoriesBySlug = [];
        foreach ($categoryRows as $row) {
            $category = Category::findBySlug($row['slug']);
            if ($category !== null) {
                $categoriesBySlug[$row['slug']] = $category;
            }
        }

        $discussionRows = [
            [
                'category_slug' => 'announcements',
                'author_email' => 'alex.admin@example.com',
                'title' => 'Welcome to the community forum',
                'slug' => 'welcome-to-the-community-forum',
                'body' => "Welcome everyone!\n\nThis forum is now open for discussions, support, and product ideas.",
                'replies' => [
                    ['author_email' => 'maya.maker@example.com', 'body' => 'Happy to be here. Looking forward to sharing updates.'],
                    ['author_email' => 'sam.support@example.com', 'body' => 'I will keep an eye on support topics and help where I can.'],
                ],
            ],
            [
                'category_slug' => 'general',
                'author_email' => 'maya.maker@example.com',
                'title' => 'What are you building this week?',
                'slug' => 'what-are-you-building-this-week',
                'body' => 'Share your weekly progress and blockers in this thread.',
                'replies' => [
                    ['author_email' => 'nina.newbie@example.com', 'body' => 'I am learning the basics and setting up my first project.'],
                    ['author_email' => 'alex.admin@example.com', 'body' => 'Great start. Keep posting updates and questions.'],
                    ['author_email' => 'sam.support@example.com', 'body' => 'If you get stuck on setup, open a support topic and tag me.'],
                ],
            ],
            [
                'category_slug' => 'support',
                'author_email' => 'nina.newbie@example.com',
                'title' => 'Trouble with local environment setup',
                'slug' => 'trouble-with-local-environment-setup',
                'body' => 'My local setup fails when running migrations. Any checklist I should follow?',
                'replies' => [
                    ['author_email' => 'sam.support@example.com', 'body' => "Start by checking PHP extensions and database credentials.\n\nThen retry after clearing cached config."],
                    ['author_email' => 'maya.maker@example.com', 'body' => 'I hit this too. Recreating the database solved it for me.'],
                ],
            ],
            [
                'category_slug' => 'showcase',
                'author_email' => 'maya.maker@example.com',
                'title' => 'Released my first plugin',
                'slug' => 'released-my-first-plugin',
                'body' => 'I shipped a small plugin this morning. Feedback is welcome.',
                'replies' => [
                    ['author_email' => 'alex.admin@example.com', 'body' => 'Nice work. Post screenshots and a short changelog.'],
                    ['author_email' => 'nina.newbie@example.com', 'body' => 'Congrats! This motivates me to finish my own addon.'],
                ],
            ],
            [
                'category_slug' => 'ideas',
                'author_email' => 'sam.support@example.com',
                'title' => 'Knowledge base integration',
                'slug' => 'knowledge-base-integration',
                'body' => 'We should add a knowledge base section and auto-suggest articles while writing support threads.',
                'replies' => [
                    ['author_email' => 'maya.maker@example.com', 'body' => 'Strong idea. It can reduce repetitive support questions.'],
                    ['author_email' => 'alex.admin@example.com', 'body' => 'I like this direction. Add a rough implementation proposal next.'],
                ],
            ],
            [
                'category_slug' => 'off-topic',
                'author_email' => 'nina.newbie@example.com',
                'title' => 'Favorite tools for productivity',
                'slug' => 'favorite-tools-for-productivity',
                'body' => 'Which tools save you the most time every day?',
                'replies' => [
                    ['author_email' => 'maya.maker@example.com', 'body' => 'Keyboard-driven editors and terminal multiplexers for me.'],
                    ['author_email' => 'sam.support@example.com', 'body' => 'A solid checklist habit helps me more than any app.'],
                ],
            ],
        ];

        $createdThreads = 0;
        $createdPosts = 0;
        $allPostIds = [];
        foreach ($discussionRows as $row) {
            $category = $categoriesBySlug[$row['category_slug']] ?? null;
            $author = $usersByEmail[strtolower($row['author_email'])] ?? null;
            if ($category === null || $author === null) {
                continue;
            }

            if (Thread::findByCategoryAndSlug((int) $category->id, $row['slug']) !== null) {
                continue;
            }

            $now = gmdate('Y-m-d H:i:s');
            $thread = Thread::create([
                'category_id' => (int) $category->id,
                'user_id' => (int) $author->id,
                'title' => $row['title'],
                'slug' => $row['slug'],
                'body' => $row['body'],
                'is_locked' => 0,
                'is_pinned' => 0,
                'reply_count' => 0,
                'last_post_at' => $now,
            ]);
            ++$createdThreads;

            $firstPost = Post::query()->where('thread_id', (int) $thread->id)->orderBy('id')->first();
            if ($firstPost !== null) {
                $allPostIds[] = (int) $firstPost->id;
                ++$createdPosts;
            }

            foreach ($row['replies'] as $reply) {
                $replyAuthor = $usersByEmail[strtolower($reply['author_email'])] ?? null;
                if ($replyAuthor === null) {
                    continue;
                }

                $replyPost = Post::create([
                    'thread_id' => (int) $thread->id,
                    'user_id' => (int) $replyAuthor->id,
                    'body' => $reply['body'],
                    'is_edited' => 0,
                    'edited_at' => null,
                ]);
                $allPostIds[] = (int) $replyPost->id;
                ++$createdPosts;
            }
            Thread::touchAfterReply((int) $thread->id);
        }

        $likesAdded = 0;
        $likedOnce = [];
        foreach ($allPostIds as $postId) {
            foreach ($usersByEmail as $email => $user) {
                if ($email === 'nina.newbie@example.com') {
                    continue;
                }
                $key = $postId . ':' . (int) $user->id;
                if (isset($likedOnce[$key])) {
                    continue;
                }
                PostLike::toggle($postId, (int) $user->id);
                $likedOnce[$key] = true;
                ++$likesAdded;
            }
        }

        foreach ($usersByEmail as $user) {
            ForumBadgeService::recalculateForUser((int) $user->id);
        }

        $this->awardBadgeByKey($usersByEmail['alex.admin@example.com'] ?? null, 'veteran');
        $this->awardBadgeByKey($usersByEmail['maya.maker@example.com'] ?? null, 'thread_starter');
        $this->awardBadgeByKey($usersByEmail['sam.support@example.com'] ?? null, 'supporter');

        return [
            'categories' => $createdCategories,
            'users' => $createdUsers,
            'threads' => $createdThreads,
            'posts' => $createdPosts,
            'likes' => $likesAdded,
        ];
    }

    private function awardBadgeByKey(?User $user, string $badgeKey): void
    {
        if ($user === null || $badgeKey === '') {
            return;
        }

        $badge = Badge::query()->where('badge_key', $badgeKey)->first();
        if ($badge === null) {
            return;
        }
        $badgeId = (int) ($badge->id ?? 0);
        if ($badgeId <= 0) {
            return;
        }

        if (UserBadge::query()
            ->where('user_id', (int) $user->id)
            ->where('badge_id', $badgeId)
            ->exists()
        ) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        UserBadge::create([
            'user_id' => (int) $user->id,
            'badge_id' => $badgeId,
            'awarded_at' => $now,
        ]);
    }
}
