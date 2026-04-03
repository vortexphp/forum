<?php

declare(strict_types=1);

namespace App\Handlers\Forum;

use App\Models\Category;
use App\Models\Thread;
use Vortex\Http\Request;
use Vortex\Http\Response;
use Vortex\Pagination\Paginator;
use Vortex\View\View;

final class CategoryHandler
{
    public function index(): Response
    {
        return View::html('forum.categories', [
            'title' => \trans('forum.categories.title'),
            'categories' => Category::listWithStats(),
        ]);
    }

    public function show(string $categorySlug): Response
    {
        $category = Category::findBySlug($categorySlug);
        if ($category === null) {
            return Response::make('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $page = max(1, (int) Request::input('page', 1));
        $payload = Thread::paginateForCategory((int) $category->id, $page, 20);
        $lastPage = max(1, (int) ceil($payload['total'] / 20));
        $pagination = new Paginator($payload['items'], $payload['total'], $page, 20, $lastPage);

        return View::html('forum.category', [
            'title' => (string) $category->name,
            'category' => $category,
            'threads' => $payload['items'],
            'pagination' => $pagination->withBasePath(route('forum.category', ['category' => $category->slug])),
        ]);
    }
}
