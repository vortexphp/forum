<?php

declare(strict_types=1);

use Vortex\Database\Connection;
use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class implements Migration {
    public function id(): string
    {
        return '20260403_000300_add_forum_social_features';
    }

    public function up(Connection $db): void
    {
        $schema = Schema::connection($db);

        $schema->create('tags', static function ($table): void {
            $table->id();
            $table->string('name', 40);
            $table->string('slug', 48)->unique();
            $table->timestamps();
        });

        $schema->create('thread_tags', static function ($table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('threads')->cascadeOnDelete()->index();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete()->index();
            $table->timestamps();
            $table->unique(['thread_id', 'tag_id'], 'thread_tags_unique');
        });

        $schema->create('post_likes', static function ($table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->timestamps();
            $table->unique(['post_id', 'user_id'], 'post_likes_unique');
        });

        $schema->create('content_flags', static function ($table): void {
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

    public function down(Connection $db): void
    {
        $schema = Schema::connection($db);
        $schema->dropIfExists('content_flags');
        $schema->dropIfExists('post_likes');
        $schema->dropIfExists('thread_tags');
        $schema->dropIfExists('tags');
    }
};
