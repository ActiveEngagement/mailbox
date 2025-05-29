<?php

use Actengage\Mailbox\Models\MailboxFolder;
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
        Schema::table('mailbox_folders', function (Blueprint $table) {
            $table->unsignedInteger('_lft')->after('parent_id');
            $table->unsignedInteger('_rgt')->after('_lft');
        });

        MailboxFolder::fixTree();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailbox_folders', function (Blueprint $table) {
            $table->dropColumn('_lft');
            $table->dropColumn('_rgt');
        });
    }
};
