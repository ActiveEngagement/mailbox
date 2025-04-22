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
        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->string('internet_message_id')->after('conversation_index')->nullable();
            $table->string('in_reply_to')->after('internet_message_id')->nullable();
            $table->text('references')->after('in_reply_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailbox_messages', function (Blueprint $table) {
            $table->dropColumn('internet_message_id');
            $table->dropColumn('in_reply_to');
            $table->dropColumn('references');
        });
    }
};
