<?php

use Actengage\Mailbox\Models\MailboxMessage;

it('sets the external_id and creates the md5 hash from the external_id', function (): void {
    $message = MailboxMessage::factory()->make([
        'external_id' => 'test',
    ]);

    expect($message->external_id)->toBe('test');
    expect($message->hash)->toBe(md5('test'));
});
