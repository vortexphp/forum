<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->string('badge', 40)->nullable()->index();
        });
    }

    public function down(): void
    {
        // Column drops are not supported by the current schema builder.
    }
};
