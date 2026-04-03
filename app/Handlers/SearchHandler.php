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

        $threads = Thread::query()
            ->select([
                'threads.title',
                'threads.slug',
                'c.slug AS category_slug',
                'c.name AS category_name',
                'u.name AS author_name',
            ])
            ->join('categories AS c', 'c.id', '=', 'threads.category_id')
            ->join('users AS u', 'u.id', '=', 'threads.user_id')
            ->whereGroup(static function ($query) use ($like): void {
                $query
                    ->whereRaw('LOWER(threads.title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(threads.body) LIKE ?', [$like]);
            })
            ->orderBy('threads.last_post_at', 'DESC')
            ->orderBy('threads.id', 'DESC')
            ->limit(8)
            ->getRaw();
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

        $categories = Category::query()
            ->select(['name', 'slug', 'description'])
            ->whereGroup(static function ($query) use ($like): void {
                $query
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$like]);
            })
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->limit(5)
            ->getRaw();
        foreach ($categories as $row) {
            $items[] = [
                'type' => 'category',
                'title' => (string) ($row['name'] ?? ''),
                'meta' => (string) ($row['description'] ?? ''),
                'extra' => '',
                'url' => route('forum.category', ['category' => (string) ($row['slug'] ?? '')]),
            ];
        }

        $users = User::query()
            ->select(['id', 'name', 'avatar'])
            ->whereRaw('LOWER(name) LIKE ?', [$like])
            ->orderBy('name', 'ASC')
            ->limit(6)
            ->getRaw();
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
