<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('forum_settings', function ($table) {
            $table->id();
            $table->string('setting_key', 120)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_settings');
    }
};
