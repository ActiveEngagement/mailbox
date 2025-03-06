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
        Schema::create('mailbox_folders', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('mailbox_id')->unsigned();
            $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('id')->on('mailbox_folders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('external_id')->unique();
            $table->string('name');
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();

            $table->index(['mailbox_id', 'parent_id']);
            $table->index(['mailbox_id', 'name']);
            $table->index(['mailbox_id', 'name', 'external_id']);
            $table->index(['mailbox_id', 'is_hidden']);
            $table->index(['mailbox_id', 'is_favorite']);
        });
    }

    /**
     * 
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailbox_folders');
    }
};
