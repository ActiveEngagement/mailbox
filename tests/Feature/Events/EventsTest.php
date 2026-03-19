<?php

use Actengage\Mailbox\Events\MailboxCreated;
use Actengage\Mailbox\Events\MailboxDeleted;
use Actengage\Mailbox\Events\MailboxDeleting;
use Actengage\Mailbox\Events\MailboxFolderCreated;
use Actengage\Mailbox\Events\MailboxFolderDeleted;
use Actengage\Mailbox\Events\MailboxFolderDeleting;
use Actengage\Mailbox\Events\MailboxFolderUpdated;
use Actengage\Mailbox\Events\MailboxMessageAttachmentCreated;
use Actengage\Mailbox\Events\MailboxMessageAttachmentDeleted;
use Actengage\Mailbox\Events\MailboxMessageAttachmentDeleting;
use Actengage\Mailbox\Events\MailboxMessageAttachmentUpdated;
use Actengage\Mailbox\Events\MailboxMessageCreated;
use Actengage\Mailbox\Events\MailboxMessageDeleted;
use Actengage\Mailbox\Events\MailboxMessageDeleting;
use Actengage\Mailbox\Events\MailboxMessageUpdated;
use Actengage\Mailbox\Events\MailboxSubscriptionCreated;
use Actengage\Mailbox\Events\MailboxSubscriptionDeleted;
use Actengage\Mailbox\Events\MailboxSubscriptionDeleting;
use Actengage\Mailbox\Events\MailboxSubscriptionUpdated;
use Actengage\Mailbox\Events\MailboxUpdated;
use Actengage\Mailbox\Events\ProcessedUrlsAsAttachments;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Actengage\Mailbox\Models\MailboxSubscription;
use Http\Promise\FulfilledPromise;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

it('dispatches mailbox events on create, update, and delete', function (): void {
    Event::fake([
        MailboxCreated::class,
        MailboxUpdated::class,
        MailboxDeleting::class,
        MailboxDeleted::class,
    ]);

    $mailbox = Mailbox::withoutBroadcasting(fn () => Mailbox::factory()->create());

    Event::assertDispatched(MailboxCreated::class, fn (MailboxCreated $e): bool => $e->model->is($mailbox));

    $mailbox->withoutBroadcasting(fn () => $mailbox->update(['email' => 'updated@test.com']));

    Event::assertDispatched(MailboxUpdated::class);

    $mailbox->withoutBroadcasting(fn () => $mailbox->delete());

    Event::assertDispatched(MailboxDeleting::class);
    Event::assertDispatched(MailboxDeleted::class);
});

it('dispatches folder events on create, update, and delete', function (): void {
    Event::fake([
        MailboxFolderCreated::class,
        MailboxFolderUpdated::class,
        MailboxFolderDeleting::class,
        MailboxFolderDeleted::class,
    ]);

    $folder = MailboxFolder::withoutBroadcasting(fn () => MailboxFolder::factory()->create());

    Event::assertDispatched(MailboxFolderCreated::class);

    $folder->withoutBroadcasting(fn () => $folder->update(['name' => 'Updated']));

    Event::assertDispatched(MailboxFolderUpdated::class);

    $folder->withoutBroadcasting(fn () => $folder->delete());

    Event::assertDispatched(MailboxFolderDeleting::class);
    Event::assertDispatched(MailboxFolderDeleted::class);
});

it('dispatches message events on create, update, and delete', function (): void {
    Event::fake([
        MailboxMessageCreated::class,
        MailboxMessageUpdated::class,
        MailboxMessageDeleting::class,
        MailboxMessageDeleted::class,
    ]);

    $message = MailboxMessage::withoutBroadcasting(fn () => MailboxMessage::factory()->create());

    Event::assertDispatched(MailboxMessageCreated::class);

    $message->withoutBroadcasting(fn () => $message->update(['subject' => 'Updated']));

    Event::assertDispatched(MailboxMessageUpdated::class);

    $message->withoutBroadcasting(fn () => $message->delete());

    Event::assertDispatched(MailboxMessageDeleting::class);
    Event::assertDispatched(MailboxMessageDeleted::class);
});

it('dispatches attachment events on create, update, and delete', function (): void {
    Event::fake([
        MailboxMessageAttachmentCreated::class,
        MailboxMessageAttachmentUpdated::class,
        MailboxMessageAttachmentDeleting::class,
        MailboxMessageAttachmentDeleted::class,
    ]);

    $attachment = MailboxMessageAttachment::withoutBroadcasting(fn () => MailboxMessageAttachment::factory()->create());

    Event::assertDispatched(MailboxMessageAttachmentCreated::class);

    $attachment->withoutBroadcasting(fn () => $attachment->update(['name' => 'updated.pdf']));

    Event::assertDispatched(MailboxMessageAttachmentUpdated::class);

    $attachment->withoutBroadcasting(fn () => $attachment->delete());

    Event::assertDispatched(MailboxMessageAttachmentDeleting::class);
    Event::assertDispatched(MailboxMessageAttachmentDeleted::class);
});

it('dispatches subscription events on create, update, and delete', function (): void {
    Event::fake([
        MailboxSubscriptionCreated::class,
        MailboxSubscriptionUpdated::class,
        MailboxSubscriptionDeleting::class,
        MailboxSubscriptionDeleted::class,
    ]);

    Subscriptions::shouldReceive('delete')->andReturn(new FulfilledPromise(null));

    $mailbox = Mailbox::withoutBroadcasting(fn () => Mailbox::factory()->create());

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'sub-evt',
        'resource' => '/users/test@test.com/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    Event::assertDispatched(MailboxSubscriptionCreated::class);

    $subscription->withoutBroadcasting(fn () => $subscription->update(['change_type' => 'updated']));

    Event::assertDispatched(MailboxSubscriptionUpdated::class);

    $subscription->withoutBroadcasting(fn () => $subscription->delete());

    Event::assertDispatched(MailboxSubscriptionDeleting::class);
    Event::assertDispatched(MailboxSubscriptionDeleted::class);
});

it('creates ProcessedUrlsAsAttachments event with the model', function (): void {
    $message = MailboxMessage::factory()->create();
    $event = new ProcessedUrlsAsAttachments($message);

    expect($event->model->is($message))->toBeTrue();
});

it('event broadcastOn returns private channel', function (): void {
    $mailbox = Mailbox::factory()->make();
    $event = new MailboxCreated($mailbox);

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxDeleted broadcastOn returns private channel', function (): void {
    $mailbox = Mailbox::factory()->make();
    $event = new MailboxDeleted($mailbox);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxDeleting broadcastOn returns private channel', function (): void {
    $mailbox = Mailbox::factory()->make();
    $event = new MailboxDeleting($mailbox);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxUpdated broadcastOn returns private channel', function (): void {
    $mailbox = Mailbox::factory()->make();
    $event = new MailboxUpdated($mailbox);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxFolderCreated broadcastOn returns private channel', function (): void {
    $folder = MailboxFolder::factory()->make();
    $event = new MailboxFolderCreated($folder);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxFolderDeleted broadcastOn returns private channel', function (): void {
    $folder = MailboxFolder::factory()->make();
    $event = new MailboxFolderDeleted($folder);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxFolderDeleting broadcastOn returns private channel', function (): void {
    $folder = MailboxFolder::factory()->make();
    $event = new MailboxFolderDeleting($folder);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxFolderUpdated broadcastOn returns private channel', function (): void {
    $folder = MailboxFolder::factory()->make();
    $event = new MailboxFolderUpdated($folder);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageCreated broadcastOn returns private channel', function (): void {
    $message = MailboxMessage::factory()->make();
    $event = new MailboxMessageCreated($message);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageDeleted broadcastOn returns private channel', function (): void {
    $message = MailboxMessage::factory()->make();
    $event = new MailboxMessageDeleted($message);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageDeleting broadcastOn returns private channel', function (): void {
    $message = MailboxMessage::factory()->make();
    $event = new MailboxMessageDeleting($message);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageUpdated broadcastOn returns private channel', function (): void {
    $message = MailboxMessage::factory()->make();
    $event = new MailboxMessageUpdated($message);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageAttachmentCreated broadcastOn returns private channel', function (): void {
    $attachment = MailboxMessageAttachment::factory()->make();
    $event = new MailboxMessageAttachmentCreated($attachment);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageAttachmentDeleted broadcastOn returns private channel', function (): void {
    $attachment = MailboxMessageAttachment::factory()->make();
    $event = new MailboxMessageAttachmentDeleted($attachment);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageAttachmentDeleting broadcastOn returns private channel', function (): void {
    $attachment = MailboxMessageAttachment::factory()->make();
    $event = new MailboxMessageAttachmentDeleting($attachment);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxMessageAttachmentUpdated broadcastOn returns private channel', function (): void {
    $attachment = MailboxMessageAttachment::factory()->make();
    $event = new MailboxMessageAttachmentUpdated($attachment);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxSubscriptionCreated broadcastOn returns private channel', function (): void {
    $subscription = new MailboxSubscription;
    $event = new MailboxSubscriptionCreated($subscription);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxSubscriptionDeleted broadcastOn returns private channel', function (): void {
    $subscription = new MailboxSubscription;
    $event = new MailboxSubscriptionDeleted($subscription);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxSubscriptionDeleting broadcastOn returns private channel', function (): void {
    $subscription = new MailboxSubscription;
    $event = new MailboxSubscriptionDeleting($subscription);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('MailboxSubscriptionUpdated broadcastOn returns private channel', function (): void {
    $subscription = new MailboxSubscription;
    $event = new MailboxSubscriptionUpdated($subscription);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});

it('ProcessedUrlsAsAttachments broadcastOn returns private channel', function (): void {
    $message = MailboxMessage::factory()->make();
    $event = new ProcessedUrlsAsAttachments($message);
    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
});
