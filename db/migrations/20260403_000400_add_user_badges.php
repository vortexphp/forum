<?php

declare(strict_types=1);

use Vortex\Database\Connection;
use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class implements Migration {
    public function id(): string
    {
        return '20260403_000400_add_user_badges';
    }

    public function up(Connection $db): void
    {
        Schema::connection($db)->table('users', static function ($table): void {
            $table->string('badge', 40)->nullable()->index();
        });
    }

    public function down(Connection $db): void
    {
        // Column drops are not supported by the current schema builder.
    }
};
