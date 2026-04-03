<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('thread_bookmarks', function ($table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->timestamps();
            $table->unique(['thread_id', 'user_id'], 'thread_bookmarks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thread_bookmarks');
    }
};
