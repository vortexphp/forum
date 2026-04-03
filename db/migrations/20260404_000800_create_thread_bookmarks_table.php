<?php

declare(strict_types=1);

use Vortex\Database\Connection;
use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class implements Migration {
    public function id(): string
    {
        return '20260404_000800_create_thread_bookmarks_table';
    }

    public function up(Connection $db): void
    {
        $schema = Schema::connection($db);

        $schema->create('thread_bookmarks', static function ($table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->timestamps();
            $table->unique(['thread_id', 'user_id'], 'thread_bookmarks_unique');
        });
    }

    public function down(Connection $db): void
    {
        Schema::connection($db)->dropIfExists('thread_bookmarks');
    }
};
