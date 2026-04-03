<?php

declare(strict_types=1);

use Vortex\Database\Connection;
use Vortex\Database\Schema\Migration;
use Vortex\Database\Schema\Schema;

return new class implements Migration {
    public function id(): string
    {
        return '20260403_000600_create_private_messages_table';
    }

    public function up(Connection $db): void
    {
        $schema = Schema::connection($db);

        $schema->create('private_messages', static function ($table): void {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete()->index();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete()->index();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['sender_id', 'recipient_id', 'created_at'], 'pm_conversation_idx');
        });
    }

    public function down(Connection $db): void
    {
        Schema::connection($db)->dropIfExists('private_messages');
    }
};
