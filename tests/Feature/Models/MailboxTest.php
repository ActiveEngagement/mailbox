<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('it scopes the query by mailbox', function (): void {
    $mailbox = Mailbox::factory()->create();

    Mailbox::factory()->count(2)->create();

    expect(Mailbox::all())->toHaveCount(3);
    expect(Mailbox::query()->mailbox($mailbox)->get())->toHaveCount(1);
    expect(Mailbox::query()->mailbox($mailbox->id)->get())->toHaveCount(1);
});

it('it scopes the query by email', function (): void {
    $mailbox = Mailbox::factory()->create();

    Mailbox::factory()->count(2)->create();

    expect(Mailbox::all())->toHaveCount(3);
    expect(Mailbox::query()->email($mailbox)->get())->toHaveCount(1);
    expect(Mailbox::query()->email($mailbox->email)->get())->toHaveCount(1);
});

it('returns the correct folder for each folder method and caches the result', function (): void {
    $mailbox = Mailbox::factory()->create();

    $folderNames = [
        'archiveFolder' => 'Archive',
        'conversationHistoryFolder' => 'Conversation History',
        'deletedItemsFolder' => 'Deleted Items',
        'draftsFolder' => 'Drafts',
        'inboxFolder' => 'Inbox',
        'junkEmailFolder' => 'Junk Email',
        'outboxFolder' => 'Outbox',
        'sentItemsFolder' => 'Sent Items',
    ];

    foreach ($folderNames as $method => $name) {
        MailboxFolder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'name' => $name,
        ]);
    }

    foreach ($folderNames as $method => $name) {
        $folder = $mailbox->$method();
        expect($folder)->toBeInstanceOf(MailboxFolder::class);
        expect($folder->name)->toBe($name);

        // Call again to verify cache returns same result
        $cachedFolder = $mailbox->$method();
        expect($cachedFolder->id)->toBe($folder->id);
    }
});

it('broadcastOn returns array with the mailbox', function (): void {
    $mailbox = Mailbox::factory()->create();

    $channels = $mailbox->broadcastOn('created');

    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(Mailbox::class);
    expect($channels[0]->id)->toBe($mailbox->id);
});

it('has folders, messages, and subscriptions relationships', function (): void {
    $mailbox = Mailbox::factory()->create();

    expect($mailbox->folders())->toBeInstanceOf(HasMany::class);
    expect($mailbox->messages())->toBeInstanceOf(HasMany::class);
    expect($mailbox->subscriptions())->toBeInstanceOf(HasMany::class);
});

it('can be created via factory', function (): void {
    $mailbox = Mailbox::factory()->create();

    expect($mailbox)->toBeInstanceOf(Mailbox::class);
    expect($mailbox->exists)->toBeTrue();
    expect($mailbox->email)->toBeString();
});
