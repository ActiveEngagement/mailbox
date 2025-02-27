<?php

use Actengage\Mailbox\Http\Controllers\FolderWebhookController;
use Actengage\Mailbox\Http\Controllers\MessageWebhookController;
use Actengage\Mailbox\Http\Middleware\HandleValidationToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::prefix('mailbox/{mailbox}/webhooks')
    ->middleware([
        SubstituteBindings::class,
        HandleValidationToken::class,
    ])
    ->group(function() {        
        Route::post('folders', FolderWebhookController::class)->name('mailbox.webhooks.folders');
        Route::post('messages', MessageWebhookController::class)->name('mailbox.webhooks.messages');
    });