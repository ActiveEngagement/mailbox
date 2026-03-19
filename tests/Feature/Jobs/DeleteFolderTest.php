<?php

use Actengage\Mailbox\Jobs\DeleteFolder;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;

it('deletes a folder by external id', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->for($mailbox)->create([
        'external_id' => 'folder-ext-123',
    ]);

    (new DeleteFolder($mailbox, 'folder-ext-123'))->handle();

    expect(MailboxFolder::find($folder->id))->toBeNull();
});

it('does nothing when folder does not exist', function (): void {
    $mailbox = Mailbox::factory()->create();

    (new DeleteFolder($mailbox, 'nonexistent'))->handle();

    expect(MailboxFolder::count())->toBe(0);
});
