<?php

use Actengage\Mailbox\Data\EmailAddress;
use Actengage\Mailbox\Models\MailboxMessage;
use Illuminate\Support\Collection;
use Microsoft\Graph\Generated\Models\EmailAddress as ModelsEmailAddress;
use Microsoft\Graph\Generated\Models\Recipient;

it('casts from accepted types correctly', function() {
    $message = MailboxMessage::factory()->make();
    
    expect($message->to)->toBeInstanceOf(Collection::class);
    expect($message->to[0])->toBeInstanceOf(EmailAddress::class);
    
    /** @var Collection<EmailAddress> */
    $message->to = [EmailAddress::fromString('John F. Doe<test@test.com>')];

    expect($message->to[0])->toBeInstanceOf(EmailAddress::class);
    expect($message->to[0]->email)->toBe('test@test.com');
    expect($message->to[0]->name)->toBe('John F. Doe');

    /** @var Collection<EmailAddress> */
    $message->to = [createRecipient()];
    
    expect($message->to)->toBeInstanceOf(Collection::class);
    expect($message->to[0])->toBeInstanceOf(EmailAddress::class);

    /** @var Collection<EmailAddress> */
    $message->to = ['John F. Doe<test@test.com>'];

    expect($message->to[0]->email)->toBe('test@test.com'); 
    expect($message->to[0]->name)->toBe('John F. Doe');
    
    /** @var Collection<EmailAddress> */
    $message->to = ['test@test.com'];

    expect($message->to[0]->email)->toBe('test@test.com'); 
    expect($message->to[0]->name)->toBeNull();

    $message->to = null;

    expect($message->to)->toBeNull();

    $message->to = [];

    expect($message->to)->toBeNull();
});