<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;

it('gets a storage path using the mailbox email and message hash', function() {
    $model = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->state([
            'email' => 'test@test.com',
        ]),
        'external_id' => 'test'
    ]);

    expect($model->attachmentRelativePath())->toBe(sprintf('%s/%s', 'test@test.com', md5('test')));
    expect($model->attachmentRelativePath('some-file.html'))->toBe(sprintf('%s/%s/some-file.html', 'test@test.com', md5('test')));
});

it('it scopes the query by folder', function() {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id
    ])->count(3), 'messages');

    $inbox = $folder->create([
        'name' => 'Inbox'
    ]);

    $drafts = $folder->create([
        'name' => 'Drafts'
    ]);

    $folder->create([
        'name' => 'Sent Items'
    ]);

    expect($mailbox->messages)->toHaveCount(9);
    expect($mailbox->messages()->folder($inbox, $drafts->id)->get())->toHaveCount(6);
});

it('it scopes the query by external id', function() {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id
    ])->count(3), 'messages');

    $inbox = $folder->create([
        'name' => 'Inbox'
    ]);

    $drafts = $folder->create([
        'name' => 'Drafts'
    ]);

    expect($mailbox->messages)->toHaveCount(6);
    expect($mailbox->messages()->externalId(...$inbox->messages)->get())->toHaveCount(3);
    expect($mailbox->messages()->externalId(...$drafts->pluck('id'))->get())->toHaveCount(3);
});

it('it scopes the query by conversation id', function() {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id,
        'conversation_id' => 'test'
    ])->count(3), 'messages');

    $folder->create([
        'name' => 'Inbox'
    ]);

    expect($mailbox->messages)->toHaveCount(3);
    expect($mailbox->messages()->conversation('test')->get())->toHaveCount(3);   
    expect($mailbox->messages()->conversation($mailbox->messages->first())->get())->toHaveCount(3);    
});

it('it scopes the query by message id', function() {
    $mailbox = Mailbox::factory()->create();

    $folder = MailboxFolder::factory([
        'mailbox_id' => $mailbox->id
    ])->has(MailboxMessage::factory()->state([
        'mailbox_id' => $mailbox->id,
        'conversation_id' => 'test'
    ])->count(3), 'messages');

    $folder->create([
        'name' => 'Inbox'
    ]);

    expect($mailbox->messages)->toHaveCount(3);
    expect($mailbox->messages()->message($mailbox->messages()->first())->get())->toHaveCount(1);   
    expect($mailbox->messages()->message(1)->get())->toHaveCount(1);    
});