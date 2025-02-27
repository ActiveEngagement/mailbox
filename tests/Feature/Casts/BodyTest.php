<?php

use Actengage\Mailbox\Models\MailboxMessage;

it('casts from accepted types correctly', function() {
    $message = MailboxMessage::factory()->make();

    expect($message->body)->toBeString();

    $message->body = fake()->randomHtml();

    expect($message->body)->toBeString();
    
    $message->body = null;

    expect($message->body)->toBeNull();
});