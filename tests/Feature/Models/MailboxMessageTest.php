<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;

it('gets a storage path using the mailbox email and message hash', function() {
    $model = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->state([
            'email' => 'test@test.com',
        ]),
        'external_id' => 'test'
    ]);

    expect($model->attachmentRelativePath())->toBe(sprintf('%s/%s', 'test@test.com', md5('test')));
    expect($model->attachmentRelativePath('some-file.html'))->toBe(sprintf('%s/%s/some-file.html', 'test@test.com', md5('test')));
});