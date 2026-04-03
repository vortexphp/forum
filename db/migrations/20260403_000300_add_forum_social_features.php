<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tags', function ($table) {
            $table->id();
            $table->string('name', 40);
            $table->string('slug', 48)->unique();
            $table->timestamps();
        });

        Schema::create('thread_tags', function ($table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete()->index();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete()->index();
            $table->timestamps();
            $table->unique(['thread_id', 'tag_id'], 'thread_tags_unique');
        });

        Schema::create('post_likes', function ($table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->timestamps();
            $table->unique(['post_id', 'user_id'], 'post_likes_unique');
        });

        Schema::create('content_flags', function ($table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete()->index();
            $table->string('target_type', 16)->index();
            $table->integer('target_id')->index();
            $table->string('reason', 80);
            $table->string('status', 16)->default('open')->index();
            $table->timestamps();
            $table->unique(['reporter_id', 'target_type', 'target_id'], 'content_flags_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_flags');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('thread_tags');
        Schema::dropIfExists('tags');
    }
};
