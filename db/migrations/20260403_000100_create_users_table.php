<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('avatar', 255)->nullable();
            $table->string('role', 20)->default('member')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
