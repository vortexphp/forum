<?php

declare(strict_types=1);

use App\Console\ForumSeedCommand;
use Vortex\Console\ConsoleApplication;

/**
 * Register application console commands.
 *
 * @return callable(ConsoleApplication): void
 */
return static function (ConsoleApplication $app): void {
    $app->register(new ForumSeedCommand($app->basePath()));
};
