<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use Vortex\Http\Csrf;
use Vortex\Http\Response;
use Vortex\Http\Session;

final class LogoutHandler
{
    public function store(): Response
    {
        if (! Csrf::validate()) {
            return Response::redirect('/', 302);
        }

        Session::start();
        Session::flushAuth();

        return Response::redirect('/', 302);
    }
}
