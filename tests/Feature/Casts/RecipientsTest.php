<?php

use Actengage\Mailbox\Data\EmailAddress;
use Actengage\Mailbox\Models\MailboxMessage;
use Illuminate\Support\Collection;
use Microsoft\Graph\Generated\Models\Recipient;

it('casts from accepted types correctly', function (): void {
    $message = MailboxMessage::factory()->make();

    expect($message->to)->toBeInstanceOf(Collection::class);
    expect($message->to[0])->toBeInstanceOf(EmailAddress::class);

    $message->to = [EmailAddress::fromString('John F. Doe<test@test.com>')];

    expect($message->to[0])->toBeInstanceOf(EmailAddress::class);
    expect($message->to[0]->email)->toBe('test@test.com');
    expect($message->to[0]->name)->toBe('John F. Doe');

    $message->to = [createRecipient()];

    expect($message->to)->toBeInstanceOf(Collection::class);
    expect($message->to[0])->toBeInstanceOf(EmailAddress::class);

    $message->to = ['John F. Doe<test@test.com>'];

    expect($message->to[0]->email)->toBe('test@test.com');
    expect($message->to[0]->name)->toBe('John F. Doe');

    $message->to = [['email' => 'test@test.com', 'name' => 'test']];

    expect($message->to[0]->email)->toBe('test@test.com');
    expect($message->to[0]->name)->toBe('test');

    $message->to = ['test@test.com'];

    expect($message->to[0]->email)->toBe('test@test.com');
    expect($message->to[0]->name)->toBeNull();

    $message->to = null;

    expect($message->to)->toBeNull();

    $message->to = [];

    expect($message->to)->toBeNull();
});

it('skips Recipient with no email address', function (): void {
    $message = MailboxMessage::factory()->make();

    $recipient = new Recipient;
    // Don't set email address

    $validRecipient = createRecipient();

    $message->to = [$recipient, $validRecipient];

    expect($message->to)->toHaveCount(1);
    expect($message->to[0]->email)->toBe('test@test.com');
});

it('throws InvalidArgumentException for unsupported type', function (): void {
    $message = MailboxMessage::factory()->make();

    $message->to = [123];
})->throws(InvalidArgumentException::class, 'Unsupported type');
