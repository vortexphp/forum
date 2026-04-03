<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('badges', function ($table) {
            $table->id();
            $table->string('badge_key', 64)->unique();
            $table->integer('sort_order')->default(100)->index();
            $table->timestamps();
        });

        Schema::create('user_badges', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->index();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete()->index();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->unique(['user_id', 'badge_id'], 'user_badges_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
