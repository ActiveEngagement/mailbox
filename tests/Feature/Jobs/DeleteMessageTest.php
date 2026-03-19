<?php

use Actengage\Mailbox\Jobs\DeleteMessage;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;

it('deletes a message by external id', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->for($mailbox)->create([
        'external_id' => 'ext-123',
    ]);

    new DeleteMessage($mailbox, 'ext-123')->handle();

    expect(MailboxMessage::query()->find($message->id))->toBeNull();
});

it('does nothing when message does not exist', function (): void {
    $mailbox = Mailbox::factory()->create();

    new DeleteMessage($mailbox, 'nonexistent')->handle();

    expect(MailboxMessage::query()->count())->toBe(0);
});
