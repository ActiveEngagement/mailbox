<?php

use Actengage\Mailbox\Enums\Importance;
use Actengage\Mailbox\Models\MailboxMessage;
use Microsoft\Graph\Generated\Models\Importance as BaseImportance;

it('casts from accepted types correctly', function (): void {
    $message = MailboxMessage::factory()->make();

    expect($message->importance)->toBe(Importance::Normal);

    $message->importance = 'low';

    expect($message->importance)->toBe(Importance::Low);

    $message->importance = Importance::Normal;

    expect($message->importance)->toBe(Importance::Normal);

    $message->importance = new BaseImportance(BaseImportance::HIGH);

    expect($message->importance)->toBe(Importance::High);
});

it('returns Normal importance for null value', function (): void {
    $message = MailboxMessage::factory()->make(['importance' => null]);
    expect($message->importance)->toBe(Importance::Normal);
});

it('returns Normal importance when get receives a non-string non-enum value', function (): void {
    $cast = new \Actengage\Mailbox\Casts\Importance;
    $model = MailboxMessage::factory()->make();

    $result = $cast->get($model, 'importance', 123, []);

    expect($result)->toBe(Importance::Normal);
});
