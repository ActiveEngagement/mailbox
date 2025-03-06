<?php

use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Illuminate\Support\Facades\Storage;

it('belongs to a mailbox and message of that mailbox', function() {
    $model = MailboxMessageAttachment::factory()->create();
    
    expect($model->message->mailbox->id)->toBe($model->mailbox->id);
});

it('has an absolute storage url', function() {
    $attachment = MailboxMessageAttachment::factory()->create([
        'name' => 'test.html'
    ]);

    expect($attachment->url)->toBe(Storage::disk($attachment->disk)->url($attachment->path));
});