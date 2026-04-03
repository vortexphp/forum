<?php

declare(strict_types=1);

use Vortex\Database\Connection;
use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class implements Migration {
    public function id(): string
    {
        return '20260404_000700_create_notifications_table';
    }

    public function up(Connection $db): void
    {
        $schema = Schema::connection($db);

        $schema->create('notifications', static function ($table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->integer('actor_id')->nullable()->index();
            $table->string('type', 48)->index();
            $table->string('title', 180);
            $table->text('body')->nullable();
            $table->string('url', 255)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'read_at', 'created_at'], 'notifications_user_read_created_idx');
        });
    }

    public function down(Connection $db): void
    {
        Schema::connection($db)->dropIfExists('notifications');
    }
};
