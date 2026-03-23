<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Illuminate\Support\Facades\Storage;

it('belongs to a mailbox and message of that mailbox', function (): void {
    $model = MailboxMessageAttachment::factory()->create();

    expect($model->message->mailbox->id)->toBe($model->mailbox->id);
});

it('has an absolute storage url', function (): void {
    $attachment = MailboxMessageAttachment::factory()->create([
        'name' => 'test.html',
    ]);

    expect($attachment->url)->toBe(Storage::disk($attachment->disk)->url($attachment->path));
});

it('scopes the query by name', function (): void {
    $attachment1 = MailboxMessageAttachment::factory()->create(['name' => 'report.pdf']);
    $attachment2 = MailboxMessageAttachment::factory()->create(['name' => 'image.png']);
    $attachment3 = MailboxMessageAttachment::factory()->create(['name' => 'report.pdf']);

    expect(MailboxMessageAttachment::query()->named('report.pdf')->get())->toHaveCount(2);
    expect(MailboxMessageAttachment::query()->named('image.png')->get())->toHaveCount(1);
});

it('returns file contents from storage', function (): void {
    $attachment = MailboxMessageAttachment::factory()->contents('Hello World')->create();

    expect($attachment->contents)->toBe('Hello World');
});

it('returns base64 encoded contents from storage', function (): void {
    $attachment = MailboxMessageAttachment::factory()->contents('Hello World')->create();

    expect($attachment->base64_contents)->toBe(base64_encode('Hello World'));
});

it('broadcastOn returns attachment, mailbox, and message', function (): void {
    $attachment = MailboxMessageAttachment::factory()->create();

    $channels = $attachment->broadcastOn('created');

    expect($channels)->toHaveCount(3);
    expect($channels[0])->toBeInstanceOf(MailboxMessageAttachment::class);
    expect($channels[1])->toBeInstanceOf(Mailbox::class);
    expect($channels[2])->toBeInstanceOf(MailboxMessage::class);
});

it('toSearchableArray returns name', function (): void {
    $attachment = MailboxMessageAttachment::factory()->create(['name' => 'document.pdf']);

    $searchable = $attachment->toSearchableArray();

    expect($searchable)->toBeArray();
    expect($searchable)->toHaveKey('name');
    expect($searchable['name'])->toBe('document.pdf');
});
