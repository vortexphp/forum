<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function ($table) {
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

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
