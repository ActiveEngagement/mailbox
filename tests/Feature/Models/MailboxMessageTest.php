<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;

it('gets a storage path using the mailbox email and message hash', function (): void {
    $model = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->state([
            'email' => 'test@test.com',
        ]),
        'external_id' => 'test',
    ]);

    expect($model->attachmentRelativePath())->toBe(sprintf('%s/%s', 'test@test.com', md5('test')));
    expect($model->attachmentRelativePath('some-file.html'))->toBe(sprintf('%s/%s/some-file.html', 'test@test.com', md5('test')));
});

it('it scopes the query by folder', function (): void {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id,
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id,
    ])->count(3), 'messages');

    $inbox = $folder->create([
        'name' => 'Inbox',
    ]);

    $drafts = $folder->create([
        'name' => 'Drafts',
    ]);

    $folder->create([
        'name' => 'Sent Items',
    ]);

    expect($mailbox->messages)->toHaveCount(9);
    expect($mailbox->messages()->folder($inbox, $drafts->id)->get())->toHaveCount(6);
});

it('it scopes the query by external id', function (): void {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id,
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id,
    ])->count(3), 'messages');

    $inbox = $folder->create([
        'name' => 'Inbox',
    ]);

    $drafts = $folder->create([
        'name' => 'Drafts',
    ]);

    expect($mailbox->messages)->toHaveCount(6);
    expect($mailbox->messages()->externalId(...$inbox->messages)->get())->toHaveCount(3);
    expect($mailbox->messages()->externalId(...$drafts->messages->pluck('external_id'))->get())->toHaveCount(3);
});

it('it scopes the query by conversation id', function (): void {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id,
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id,
        'conversation_id' => 'test',
    ])->count(3), 'messages');

    $folder->create([
        'name' => 'Inbox',
    ]);

    expect($mailbox->messages)->toHaveCount(3);
    expect($mailbox->messages()->conversation('test')->get())->toHaveCount(3);
    expect($mailbox->messages()->conversation($mailbox->messages->first())->get())->toHaveCount(3);
});

it('it scopes the query by message id', function (): void {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id,
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id,
        'conversation_id' => 'test',
    ])->count(3), 'messages');

    $folder->create([
        'name' => 'Inbox',
    ]);

    expect($mailbox->messages)->toHaveCount(3);
    expect($mailbox->messages()->message($mailbox->messages()->first())->get())->toHaveCount(1);
    expect($mailbox->messages()->message(1)->get())->toHaveCount(1);
});

it('scopes the query by mailbox model and by id', function (): void {
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();

    MailboxMessage::factory()->count(2)->create([
        'mailbox_id' => $mailbox1->id,
    ]);

    MailboxMessage::factory()->count(3)->create([
        'mailbox_id' => $mailbox2->id,
    ]);

    expect(MailboxMessage::query()->mailbox($mailbox1)->get())->toHaveCount(2);
    expect(MailboxMessage::query()->mailbox($mailbox2->id)->get())->toHaveCount(3);
});

it('broadcastOn returns message, folder, and mailbox', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->create(['mailbox_id' => $mailbox->id]);
    $message = MailboxMessage::factory()->create([
        'mailbox_id' => $mailbox->id,
        'folder_id' => $folder->id,
    ]);

    $channels = $message->broadcastOn('created');

    expect($channels)->toHaveCount(3);
    expect($channels[0])->toBeInstanceOf(MailboxMessage::class);
    expect($channels[1])->toBeInstanceOf(MailboxFolder::class);
    expect($channels[2])->toBeInstanceOf(Mailbox::class);
});

it('broadcastOn without folder filters out null', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create([
        'mailbox_id' => $mailbox->id,
        'folder_id' => null,
    ]);

    $channels = $message->broadcastOn('created');

    expect($channels)->toHaveCount(2);
    expect($channels[0])->toBeInstanceOf(MailboxMessage::class);
    expect($channels[1])->toBeInstanceOf(Mailbox::class);
});

it('toSearchableArray returns from and subject', function (): void {
    $message = MailboxMessage::factory()->create([
        'subject' => 'Test Subject',
    ]);

    $searchable = $message->toSearchableArray();

    expect($searchable)->toBeArray();
    expect($searchable)->toHaveKeys(['from', 'subject']);
    expect($searchable['subject'])->toBe('Test Subject');
});
