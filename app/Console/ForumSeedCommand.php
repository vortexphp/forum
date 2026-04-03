<?php

declare(strict_types=1);

namespace App\Console;

use App\Models\Category;
use Vortex\Application;
use Vortex\Console\Command;
use Vortex\Console\Input;

final class ForumSeedCommand implements Command
{
    public function __construct(
        private readonly string $basePath,
    ) {
    }

    public function name(): string
    {
        return 'forum:seed';
    }

    public function description(): string
    {
        return 'Seed starter forum categories (General, Announcements, Support).';
    }

    public function run(Input $input): int
    {
        require_once $this->basePath . '/vendor/autoload.php';
        Application::boot($this->basePath);

        $defaults = [
            ['name' => 'General', 'slug' => 'general', 'icon' => '💬', 'color' => '#10b981', 'description' => 'General community discussion.', 'sort_order' => 1],
            ['name' => 'Announcements', 'slug' => 'announcements', 'icon' => '📢', 'color' => '#f59e0b', 'description' => 'Official project updates.', 'sort_order' => 2],
            ['name' => 'Support', 'slug' => 'support', 'icon' => '🛟', 'color' => '#3b82f6', 'description' => 'Ask questions and get help.', 'sort_order' => 3],
        ];

        $created = 0;
        foreach ($defaults as $row) {
            if (Category::findBySlug($row['slug']) !== null) {
                continue;
            }

            Category::create([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'icon' => $row['icon'],
                'color' => $row['color'],
                'description' => $row['description'],
                'sort_order' => $row['sort_order'],
                'is_locked' => 0,
            ]);
            ++$created;
        }

        fwrite(STDERR, "Seeded {$created} category(ies).\n");

        return 0;
    }
}
