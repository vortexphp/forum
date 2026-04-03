<?php

declare(strict_types=1);

namespace App\Models;

use Vortex\Database\Model;

final class Thread extends Model
{
    /** @var list<string> */
    protected static array $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'body',
        'is_locked',
        'is_pinned',
        'reply_count',
        'last_post_at',
    ];

    public function category(): ?Category
    {
        /** @var Category|null $category */
        $category = $this->belongsTo(Category::class, 'category_id');

        return $category;
    }

    public function author(): ?User
    {
        /** @var User|null $author */
        $author = $this->belongsTo(User::class, 'user_id');

        return $author;
    }

    /**
     * @return list<Post>
     */
    public function posts(): array
    {
        /** @var list<Post> $posts */
        $posts = $this->hasMany(Post::class, 'thread_id');

        return $posts;
    }

    /**
     * @return list<Tag>
     */
    public function relatedTags(): array
    {
        /** @var list<Tag> $tags */
        $tags = $this->belongsToMany(
            Tag::class,
            'thread_tags',
            'thread_id',
            'tag_id',
        );

        return $tags;
    }

    /**
     * @return list<ThreadBookmark>
     */
    public function bookmarks(): array
    {
        /** @var list<ThreadBookmark> $bookmarks */
        $bookmarks = $this->hasMany(ThreadBookmark::class, 'thread_id');

        return $bookmarks;
    }

    /**
     * @return list<ContentFlag>
     */
    public function flags(): array
    {
        $all = $this->hasMany(ContentFlag::class, 'target_id');
        $filtered = [];
        foreach ($all as $flag) {
            if ((string) ($flag->target_type ?? '') === 'thread') {
                $filtered[] = $flag;
            }
        }

        return $filtered;
    }

    /**
     * @return list<string>
     */
    protected static function excludedFromUpdate(): array
    {
        return ['user_id', 'category_id'];
    }

    public static function findByCategoryAndSlug(int $categoryId, string $slug): ?self
    {
        return static::query()
            ->where('category_id', $categoryId)
            ->where('slug', trim(strtolower($slug)))
            ->first();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public static function paginateForCategory(int $categoryId, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $count = static::connection()->selectOne(
            'SELECT COUNT(*) AS n FROM threads WHERE category_id = ?',
            [$categoryId],
        );
        $total = (int) ($count['n'] ?? 0);

        $items = static::connection()->select(
            'SELECT t.*, u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role,'
            . ' (SELECT b.badge_key FROM user_badges ub INNER JOIN badges b ON b.id = ub.badge_id'
            . '   WHERE ub.user_id = u.id ORDER BY b.sort_order ASC, ub.awarded_at ASC LIMIT 1) AS author_primary_badge'
            . ', lu.name AS last_comment_author_name, lu.avatar AS last_comment_author_avatar,'
            . ' (SELECT b2.badge_key FROM user_badges ub2 INNER JOIN badges b2 ON b2.id = ub2.badge_id'
            . '   WHERE ub2.user_id = lu.id ORDER BY b2.sort_order ASC, ub2.awarded_at ASC LIMIT 1) AS last_comment_author_primary_badge'
            . ' FROM threads t'
            . ' INNER JOIN users u ON u.id = t.user_id'
            . ' LEFT JOIN posts lp ON lp.id = ('
            . '   SELECT p2.id FROM posts p2'
            . '   WHERE p2.thread_id = t.id'
            . '   ORDER BY p2.created_at DESC, p2.id DESC'
            . '   LIMIT 1'
            . ' )'
            . ' LEFT JOIN users lu ON lu.id = lp.user_id'
            . ' WHERE t.category_id = ?'
            . ' ORDER BY t.is_pinned DESC, t.last_post_at DESC, t.id DESC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$categoryId],
        );

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findWithAuthor(int $threadId): ?array
    {
        return static::connection()->selectOne(
            'SELECT t.*, u.name AS author_name, u.avatar AS author_avatar, u.role AS author_role,'
            . ' (SELECT b.badge_key FROM user_badges ub INNER JOIN badges b ON b.id = ub.badge_id'
            . '   WHERE ub.user_id = u.id ORDER BY b.sort_order ASC, ub.awarded_at ASC LIMIT 1) AS author_primary_badge'
            . ' FROM threads t'
            . ' INNER JOIN users u ON u.id = t.user_id'
            . ' WHERE t.id = ?'
            . ' LIMIT 1',
            [$threadId],
        );
    }

    public static function touchAfterReply(int $threadId): void
    {
        static::connection()->execute(
            'UPDATE threads'
            . ' SET reply_count = (SELECT COUNT(*) - 1 FROM posts WHERE thread_id = ?),'
            . ' last_post_at = ?,'
            . ' updated_at = ?'
            . ' WHERE id = ?',
            [$threadId, gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s'), $threadId],
        );
    }

    /**
     * @param list<string> $tags
     */
    public static function syncTags(int $threadId, array $tags): void
    {
        static::connection()->execute('DELETE FROM thread_tags WHERE thread_id = ?', [$threadId]);
        foreach ($tags as $label) {
            $name = trim($label);
            if ($name === '') {
                continue;
            }
            $slug = self::slugifyTag($name);
            $tag = Tag::findBySlug($slug);
            if ($tag === null) {
                $tag = Tag::create(['name' => $name, 'slug' => $slug]);
            }
            static::connection()->execute(
                'INSERT INTO thread_tags (thread_id, tag_id, created_at, updated_at) VALUES (?, ?, ?, ?)',
                [$threadId, (int) $tag->id, gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s')],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function tags(int $threadId): array
    {
        return Tag::forThread($threadId);
    }

    private static function slugifyTag(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'tag';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function latestByUser(int $userId, int $limit = 8): array
    {
        $limit = max(1, min(30, $limit));

        return static::connection()->select(
            'SELECT t.*, c.name AS category_name, c.slug AS category_slug'
            . ' FROM threads t'
            . ' INNER JOIN categories c ON c.id = t.category_id'
            . ' WHERE t.user_id = ?'
            . ' ORDER BY t.created_at DESC, t.id DESC'
            . ' LIMIT ' . $limit,
            [$userId],
        );
    }
}
