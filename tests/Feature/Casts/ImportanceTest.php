<?php

use Actengage\Mailbox\Enums\Importance;
use Actengage\Mailbox\Models\MailboxMessage;
use Microsoft\Graph\Generated\Models\Importance as BaseImportance;

it('casts from accepted types correctly', function() {
    $message = MailboxMessage::factory()->make();

    expect($message->importance)->toBe(Importance::Normal);

    $message->importance = 'low';

    expect($message->importance)->toBe(Importance::Low);

    $message->importance = Importance::Normal;

    expect($message->importance)->toBe(Importance::Normal);

    /** @var Importance */
    $message->importance = new BaseImportance(BaseImportance::HIGH);

    expect($message->importance)->toBe(Importance::High);
});