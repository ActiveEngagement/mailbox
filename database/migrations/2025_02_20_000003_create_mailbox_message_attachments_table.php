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
        Schema::create('mailbox_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('mailbox_id')->unsigned();
            $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('message_id')->unsigned();
            $table->foreign('message_id')->references('id')->on('mailbox_messages')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name')->index();
            $table->integer('size');
            $table->string('content_type');
            $table->string('disk');
            $table->string('path')->unique();
            $table->timestamp('last_modified_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailbox_message_attachments');
    }
};
