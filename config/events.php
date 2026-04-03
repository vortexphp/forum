<?php

declare(strict_types=1);

/**
 * Map event class names to listener class names (or a single class string).
 * Listeners are resolved from the container; use `handle($event)` or `__invoke($event)`.
 *
 * Example:
 *   UserRegistered::class => [SendWelcomeEmail::class, LogSignup::class],
 */
return [
    'listen' => [
        // App\Events\OrderPlaced::class => [App\Listeners\NotifyWarehouse::class],
    ],
];
