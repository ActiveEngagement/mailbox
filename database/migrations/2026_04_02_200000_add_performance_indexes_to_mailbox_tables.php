<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('mailbox.database_connection'))->table('mailbox_message_attachments', function (Blueprint $table): void {
            $table->index('message_id');
        });

        Schema::connection(config('mailbox.database_connection'))->table('mailbox_messages', function (Blueprint $table): void {
            $table->index(
                ['mailbox_id', 'folder_id', 'conversation_id', 'is_draft'],
                'mailbox_messages_meta_data_index'
            );
        });
    }

    public function down(): void
    {
        Schema::connection(config('mailbox.database_connection'))->table('mailbox_message_attachments', function (Blueprint $table): void {
            $table->dropIndex(['message_id']);
        });

        Schema::connection(config('mailbox.database_connection'))->table('mailbox_messages', function (Blueprint $table): void {
            $table->dropIndex('mailbox_messages_meta_data_index');
        });
    }
};
