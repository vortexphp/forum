<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\Category;
use App\Models\Thread;
use App\Models\User;
use Vortex\Http\Request;
use Vortex\Http\Response;

final class SearchHandler
{
    public function suggest(): Response
    {
        $raw = trim((string) Request::input('q', ''));
        if ($raw === '' || mb_strlen($raw) < 2) {
            return $this->json(['query' => $raw, 'items' => []]);
        }

        $q = mb_strtolower(mb_substr($raw, 0, 120));
        $like = '%' . $q . '%';
        $items = [];

        $threads = Thread::connection()->select(
            'SELECT t.title, t.slug, c.slug AS category_slug, c.name AS category_name,'
            . ' u.name AS author_name'
            . ' FROM threads t'
            . ' INNER JOIN categories c ON c.id = t.category_id'
            . ' INNER JOIN users u ON u.id = t.user_id'
            . ' WHERE LOWER(t.title) LIKE ? OR LOWER(t.body) LIKE ?'
            . ' ORDER BY t.last_post_at DESC, t.id DESC'
            . ' LIMIT 8',
            [$like, $like],
        );
        foreach ($threads as $row) {
            $items[] = [
                'type' => 'thread',
                'title' => (string) ($row['title'] ?? ''),
                'meta' => '#' . (string) ($row['category_name'] ?? ''),
                'extra' => (string) ($row['author_name'] ?? ''),
                'url' => route('forum.thread.show', [
                    'category' => (string) ($row['category_slug'] ?? ''),
                    'thread' => (string) ($row['slug'] ?? ''),
                ]),
            ];
        }

        $categories = Category::connection()->select(
            'SELECT name, slug, description FROM categories'
            . ' WHERE LOWER(name) LIKE ? OR LOWER(COALESCE(description, \'\')) LIKE ?'
            . ' ORDER BY sort_order ASC, name ASC'
            . ' LIMIT 5',
            [$like, $like],
        );
        foreach ($categories as $row) {
            $items[] = [
                'type' => 'category',
                'title' => (string) ($row['name'] ?? ''),
                'meta' => (string) ($row['description'] ?? ''),
                'extra' => '',
                'url' => route('forum.category', ['category' => (string) ($row['slug'] ?? '')]),
            ];
        }

        $users = User::connection()->select(
            'SELECT id, name, avatar FROM users'
            . ' WHERE LOWER(name) LIKE ?'
            . ' ORDER BY name ASC'
            . ' LIMIT 6',
            [$like],
        );
        foreach ($users as $row) {
            $items[] = [
                'type' => 'user',
                'title' => (string) ($row['name'] ?? ''),
                'meta' => '',
                'extra' => '',
                'avatar' => (string) ($row['avatar'] ?? ''),
                'url' => route('profile.show', ['user' => (int) ($row['id'] ?? 0)]),
            ];
        }

        return $this->json([
            'query' => $raw,
            'items' => array_slice($items, 0, 20),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status = 200): Response
    {
        return Response::make(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $status,
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }
}
