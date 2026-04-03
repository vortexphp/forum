<?php

declare(strict_types=1);

use Vortex\Database\Connection;
use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class implements Migration {
    public function id(): string
    {
        return '20260403_000500_add_forum_badges';
    }

    public function up(Connection $db): void
    {
        $schema = Schema::connection($db);

        $schema->create('badges', static function ($table): void {
            $table->id();
            $table->string('badge_key', 64)->unique();
            $table->integer('sort_order')->default(100)->index();
            $table->timestamps();
        });

        $schema->create('user_badges', static function ($table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete()->index();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->unique(['user_id', 'badge_id'], 'user_badges_unique');
        });
    }

    public function down(Connection $db): void
    {
        $schema = Schema::connection($db);
        $schema->dropIfExists('user_badges');
        $schema->dropIfExists('badges');
    }
};
