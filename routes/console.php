<?php

declare(strict_types=1);

use App\Console\ForumSeedCommand;
use Vortex\Vortex;

/**
 * Application console routes. Loaded from `app/Routes/*Console.php` (see RouteDiscovery).
 */

Vortex::command(ForumSeedCommand::class);
