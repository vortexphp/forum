<?php

declare(strict_types=1);

use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('private_messages', function ($table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete()->index();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete()->index();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['sender_id', 'recipient_id', 'created_at'], 'pm_conversation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_messages');
    }
};
