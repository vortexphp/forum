<?php

declare(strict_types=1);

/**
 * Front controller.
 * HTTP routes are discovered from app/Routes/*.php.
 */

use Vortex\Application;
use Vortex\Container;
use Vortex\Http\Csrf;
use Vortex\Http\Kernel;
use Vortex\Support\Benchmark;
use Vortex\View\View;

$projectRoot = dirname(__DIR__);
$container = null;

try {
    require $projectRoot . '/vendor/autoload.php';
    Benchmark::start('request');

    /** @var \Vortex\Container $container */
    $container = Application::boot($projectRoot, static function (Container $container, string $basePath): void {
        View::share('csrfToken', Csrf::token());
    })->container();

    (new Kernel($container))->send();
} catch (\Throwable $exception) {
    error_log($exception->getMessage() . "\n" . $exception->getTraceAsString());

    if ($container instanceof \Vortex\Container) {
        $container->make(\Vortex\Http\ErrorRenderer::class)->exception($exception)->send();

        exit;
    }

    // Hard fallback when the container fails before the framework can handle rendering.
    \Vortex\Http\Response::make(
        "Application failed to start.\n",
        500,
        ['Content-Type' => 'text/plain; charset=utf-8'],
    )->withSecurityHeaders()->send();
}
