<?php

use Actengage\Mailbox\Models\MailboxMessageAttachment;

it('belongs to a mailbox and message of that mailbox', function() {
    $model = MailboxMessageAttachment::factory()->create();
    
    expect($model->message->mailbox->id)->toBe($model->mailbox->id);
});

it('has an absolute storage url', function() {
    expect(MailboxMessageAttachment::factory()->create()->url())->toBeString();
});