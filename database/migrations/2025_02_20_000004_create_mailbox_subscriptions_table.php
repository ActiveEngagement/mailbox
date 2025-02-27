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
        Schema::create('mailbox_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->integer('mailbox_id')->unsigned();
            $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('external_id')->unique();
            $table->string('resource')->unique();
            $table->string('change_type');
            $table->string('notification_url');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailbox_subscriptions');
    }
};
