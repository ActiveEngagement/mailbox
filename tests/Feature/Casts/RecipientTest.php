<?php

use Actengage\Mailbox\Data\EmailAddress;
use Actengage\Mailbox\Models\MailboxMessage;
use Microsoft\Graph\Generated\Models\EmailAddress as ModelsEmailAddress;
use Microsoft\Graph\Generated\Models\Recipient;

it('casts from accepted types correctly', function() {
    $message = MailboxMessage::factory()->make();

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    
    $message->from = EmailAddress::fromString('John F. Doe<test@test.com>');

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    expect($message->from->email)->toBe('test@test.com');
    expect($message->from->name)->toBe('John F. Doe');
    
    $message->from = createRecipient();

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    
    /** @var EmailAddress */
    $message->from = 'John F. Doe<test@test.com>';

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    expect($message->from->email)->toBe('test@test.com');
    expect($message->from->name)->toBe('John F. Doe');
    
    /** @var EmailAddress */
    $message->from = 'test@test.com';

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    expect($message->from->email)->toBe('test@test.com');
    expect($message->from->name)->toBeNull();

    $message->from = null;

    expect($message->from)->toBeNull();
});