<?php

declare(strict_types=1);

use Vortex\Database\Connection;
use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class implements Migration {
    public function id(): string
    {
        return '20260403_000100_create_users_table';
    }

    public function up(Connection $db): void
    {
        $schema = Schema::connection($db);
        $schema->create('users', static function ($table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('avatar', 255)->nullable();
            $table->string('role', 20)->default('member')->index();
            $table->timestamps();
        });
    }

    public function down(Connection $db): void
    {
        Schema::connection($db)->dropIfExists('users');
    }
};
