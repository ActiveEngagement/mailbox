<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mailbox_messages', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('mailbox_id')->unsigned();
            $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('folder_id')->unsigned()->nullable();
            $table->foreign('folder_id')->references('id')->on('mailbox_folders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('hash')->unique();
            $table->string('external_id')->unique();
            $table->string('conversation_id')->nullable()->index();
            $table->binary('conversation_index', 512)->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_draft')->default(true);
            $table->enum('flag', ['flagged', 'notFlagged', 'complete'])->default('notFlagged');
            $table->enum('importance', ['low', 'normal', 'high'])->default('normal');
            $table->string('from')->nullable();
            $table->json('to')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->json('reply_to')->nullable();
            $table->string('subject', 512)->nullable();
            $table->mediumText('body')->nullable();
            $table->mediumText('body_preview')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['mailbox_id', 'folder_id']);
            $table->index(['mailbox_id', 'folder_id', 'from']);
            $table->index(['mailbox_id', 'folder_id', 'received_at']);
            $table->index(['mailbox_id', 'folder_id', 'conversation_id']);
            $table->index(['mailbox_id', 'received_at']);
            $table->index(['mailbox_id', 'conversation_id']);

            // if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            //     $table->fullText(['from', 'subject', 'body_preview']);
            // }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailbox_messages');
    }
};
