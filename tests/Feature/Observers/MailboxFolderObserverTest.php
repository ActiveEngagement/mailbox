<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Observers\MailboxFolderObserver;
use Illuminate\Support\Facades\Cache;

it('busts cache when a folder is created', function (): void {
    $mailbox = Mailbox::factory()->create();

    Cache::put("mailbox.{$mailbox->id}.folders.archive", 'cached');
    Cache::put("mailbox.{$mailbox->id}.folders.drafts", 'cached');
    Cache::put("mailbox.{$mailbox->id}.folders.sentItems", 'cached');
    Cache::put("mailbox.{$mailbox->id}.folders.deletedItems", 'cached');

    MailboxFolder::factory()->for($mailbox)->create();

    expect(Cache::get("mailbox.{$mailbox->id}.folders.archive"))->toBeNull();
    expect(Cache::get("mailbox.{$mailbox->id}.folders.drafts"))->toBeNull();
    expect(Cache::get("mailbox.{$mailbox->id}.folders.sentItems"))->toBeNull();
    expect(Cache::get("mailbox.{$mailbox->id}.folders.deletedItems"))->toBeNull();
});

it('busts cache when a folder is updated', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->for($mailbox)->create();

    Cache::put("mailbox.{$mailbox->id}.folders.archive", 'cached');

    $folder->update(['name' => 'Updated']);

    expect(Cache::get("mailbox.{$mailbox->id}.folders.archive"))->toBeNull();
});

it('busts cache when a folder is deleted', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->for($mailbox)->create();

    Cache::put("mailbox.{$mailbox->id}.folders.archive", 'cached');

    $folder->delete();

    expect(Cache::get("mailbox.{$mailbox->id}.folders.archive"))->toBeNull();
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
