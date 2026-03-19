<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Observers\MailboxFolderObserver;
use Illuminate\Support\Facades\Cache;

it('busts cache when a folder is created', function (): void {
    $mailbox = Mailbox::factory()->create();

    Cache::put(sprintf('mailbox.%d.folders.archive', $mailbox->id), 'cached');
    Cache::put(sprintf('mailbox.%d.folders.drafts', $mailbox->id), 'cached');
    Cache::put(sprintf('mailbox.%d.folders.sentItems', $mailbox->id), 'cached');
    Cache::put(sprintf('mailbox.%d.folders.deletedItems', $mailbox->id), 'cached');

    MailboxFolder::factory()->for($mailbox)->create();

    expect(Cache::get(sprintf('mailbox.%d.folders.archive', $mailbox->id)))->toBeNull();
    expect(Cache::get(sprintf('mailbox.%d.folders.drafts', $mailbox->id)))->toBeNull();
    expect(Cache::get(sprintf('mailbox.%d.folders.sentItems', $mailbox->id)))->toBeNull();
    expect(Cache::get(sprintf('mailbox.%d.folders.deletedItems', $mailbox->id)))->toBeNull();
});

it('busts cache when a folder is updated', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->for($mailbox)->create();

    Cache::put(sprintf('mailbox.%d.folders.archive', $mailbox->id), 'cached');

    $folder->update(['name' => 'Updated']);

    expect(Cache::get(sprintf('mailbox.%d.folders.archive', $mailbox->id)))->toBeNull();
});

it('busts cache when a folder is deleted', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->for($mailbox)->create();

    Cache::put(sprintf('mailbox.%d.folders.archive', $mailbox->id), 'cached');

    $folder->delete();

    expect(Cache::get(sprintf('mailbox.%d.folders.archive', $mailbox->id)))->toBeNull();
});

it('busts cache on restored', function (): void {
    $folder = MailboxFolder::factory()->create();

    Cache::shouldReceive('forget')->times(4);

    $observer = new MailboxFolderObserver;
    $observer->restored($folder);
});

it('busts cache on force deleted', function (): void {
    $folder = MailboxFolder::factory()->create();

    Cache::shouldReceive('forget')->times(4);

    $observer = new MailboxFolderObserver;
    $observer->forceDeleted($folder);
});
