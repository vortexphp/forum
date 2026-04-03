<?php

declare(strict_types=1);

namespace App\Console;

use App\Support\ForumSeedService;
use Vortex\Console\Command;
use Vortex\Console\Input;

final class ForumSeedCommand extends Command
{
    public function description(): string
    {
        return 'Seed forum categories, users, badges, and sample discussions.';
    }

    protected function execute(Input $input): int
    {
        $summary = (new ForumSeedService())->seed();

        $this->info(
            "Seeded {$summary['categories']} category(ies), {$summary['users']} user(s), {$summary['threads']} thread(s), {$summary['posts']} post(s), {$summary['likes']} like(s)."
        );

        return 0;
    }

    protected function shouldBootApplication(): bool
    {
        return true;
    }
}
