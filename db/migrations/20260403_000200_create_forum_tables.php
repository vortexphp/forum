<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 150)->unique();
            $table->string('icon', 32)->default('💬');
            $table->string('color', 16)->default('#10b981');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_locked')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('threads', function ($table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->string('title', 180);
            $table->string('slug', 200)->index();
            $table->text('body');
            $table->boolean('is_locked')->default(false)->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->integer('reply_count')->default(0);
            $table->timestamp('last_post_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['category_id', 'slug'], 'threads_category_slug_unique');
            $table->index(['category_id', 'is_pinned', 'last_post_at'], 'threads_category_order_index');
        });

        Schema::create('posts', function ($table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->text('body');
            $table->boolean('is_edited')->default(false)->index();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->index(['thread_id', 'created_at'], 'posts_thread_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('threads');
        Schema::dropIfExists('categories');
    }
};
