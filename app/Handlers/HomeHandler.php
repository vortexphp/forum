<?php

declare(strict_types=1);

namespace App\Handlers;

use Vortex\Http\Response;
use Vortex\View\View;

final class HomeHandler
{
    public function index(): Response
    {
        return View::html('home', [
            'title' => \trans('home.title'),
        ]);
    }
}
