<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;

it('it scopes the query by folder', function (): void {
    $mailbox = Mailbox::factory()
        ->has(MailboxFolder::factory()->state([
            'name' => 'Inbox',
        ]), 'folders')
        ->has(MailboxFolder::factory()->state([
            'name' => 'Drafts',
        ]), 'folders')
        ->has(MailboxFolder::factory()->state([
            'name' => 'Send Items',
        ]), 'folders')
        ->create([
            'email' => 'test@test.com',
        ]);

    $inbox = $mailbox->folders->firstWhere('name', 'Inbox');
    $drafts = $mailbox->folders->firstWhere('name', 'Drafts');

    expect($mailbox->folders)->toHaveCount(3);
    expect($mailbox->folders()->folder($inbox, $drafts->id)->get())->toHaveCount(2);
});

it('it scopes the query by parent', function (): void {
    $mailbox = Mailbox::factory()->create([
        'email' => 'test@test.com',
    ]);

    $inbox = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'name' => 'Inbox',
    ]);

    MailboxFolder::factory()->count(2)->create([
        'mailbox_id' => $mailbox->id,
        'parent_id' => $inbox->id,
    ]);

    $test = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'name' => 'Test Folder',
    ]);

    MailboxFolder::factory()->count(2)->create([
        'mailbox_id' => $mailbox->id,
        'parent_id' => $test->id,
    ]);

    expect($mailbox->folders()->get())->toHaveCount(6);
    expect($mailbox->folders()->parent($inbox)->get())->toHaveCount(2);
    expect($mailbox->folders()->parent($test)->get())->toHaveCount(2);
    expect($mailbox->folders()->parent($inbox, $test->id)->get())->toHaveCount(4);
});

it('it scopes the query by external id', function (): void {
    $mailbox = Mailbox::factory()->create([
        'email' => 'test@test.com',
    ]);

    $inbox = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'name' => 'Inbox',
    ]);

    $drafts = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'name' => 'Drafts',
    ]);

    expect($mailbox->folders()->externalId($inbox, $drafts->external_id)->get())->toHaveCount(2);
});

it('scopes the query by mailbox', function (): void {
    $mailbox = Mailbox::factory()->create();

    MailboxFolder::factory()->count(2)->create(['mailbox_id' => $mailbox->id]);

    $query = MailboxFolder::query()->mailbox($mailbox);

    expect($query->toRawSql())->toContain('mailbox_id');
});

it('broadcastOn returns folder and mailbox', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->create(['mailbox_id' => $mailbox->id]);

    $channels = $folder->broadcastOn('created');

    expect($channels)->toHaveCount(2);
    expect($channels[0])->toBeInstanceOf(MailboxFolder::class);
    expect($channels[1])->toBeInstanceOf(Mailbox::class);
});
