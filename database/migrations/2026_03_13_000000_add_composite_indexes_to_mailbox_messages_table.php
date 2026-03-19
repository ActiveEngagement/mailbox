<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table): void {
            $table->index(['mailbox_id', 'folder_id', 'is_read']);
            $table->index(['mailbox_id', 'folder_id', 'flag']);
        });
    }

    public function down(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table): void {
            $table->dropIndex(['mailbox_id', 'folder_id', 'is_read']);
            $table->dropIndex(['mailbox_id', 'folder_id', 'flag']);
        });
    }
};
