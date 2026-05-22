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
        Schema::connection(config('mailbox.database_connection'))->table('mailbox_messages', function (Blueprint $table): void {
            $table->mediumText('body_message')->after('body_preview')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('mailbox.database_connection'))->table('mailbox_messages', function (Blueprint $table): void {
            $table->dropColumn('body_message');
        });
    }
};
