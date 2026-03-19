<?php

use Actengage\Mailbox\Data\EmailAddress;
use Actengage\Mailbox\Models\MailboxMessage;
use Microsoft\Graph\Generated\Models\Recipient;

it('casts from accepted types correctly', function (): void {
    $message = MailboxMessage::factory()->make();

    expect($message->from)->toBeInstanceOf(EmailAddress::class);

    $message->from = EmailAddress::fromString('John F. Doe<test@test.com>');

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    expect($message->from->email)->toBe('test@test.com');
    expect($message->from->name)->toBe('John F. Doe');

    $message->from = createRecipient();

    expect($message->from)->toBeInstanceOf(EmailAddress::class);

    $message->from = 'John F. Doe<test@test.com>';

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    expect($message->from->email)->toBe('test@test.com');
    expect($message->from->name)->toBe('John F. Doe');

    $message->from = ['email' => 'test@test.com', 'name' => 'test'];

    expect($message->from->email)->toBe('test@test.com');
    expect($message->from->name)->toBe('test');

    $message->from = 'test@test.com';

    expect($message->from)->toBeInstanceOf(EmailAddress::class);
    expect($message->from->email)->toBe('test@test.com');
    expect($message->from->name)->toBeNull();

    $message->from = null;

    expect($message->from)->toBeNull();
});

it('returns null when setting Recipient with no email address', function (): void {
    $message = MailboxMessage::factory()->make();

    $recipient = new Recipient;
    // Don't set email address
    $message->from = $recipient;

    expect($message->from)->toBeNull();
});
