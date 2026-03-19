<?php

use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Actengage\Mailbox\Observers\MailboxSubscriptionObserver;
use Http\Promise\FulfilledPromise;

it('calls Subscriptions::delete when a subscription is deleted', function (): void {
    $mailbox = Mailbox::factory()->create();

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'sub-ext-123',
        'resource' => '/users/test@test.com/messages',
        'change_type' => 'created,updated,deleted',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    Subscriptions::shouldReceive('delete')
        ->once()
        ->withArgs(fn (MailboxSubscription $sub): bool => $sub->is($subscription))
        ->andReturn(new FulfilledPromise(null));

    $subscription->delete();
});

it('handles created event', function (): void {
    $subscription = MailboxSubscription::withoutBroadcasting(fn () => Mailbox::factory()->create()->subscriptions()->create([
        'external_id' => 'test-sub',
        'resource' => '/users/test/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    $observer = new MailboxSubscriptionObserver;
    $observer->created($subscription);
    // no-op, just ensure no exception
    expect(true)->toBeTrue();
});

it('handles updated event', function (): void {
    $subscription = MailboxSubscription::withoutBroadcasting(fn () => Mailbox::factory()->create()->subscriptions()->create([
        'external_id' => 'test-sub',
        'resource' => '/users/test/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    $observer = new MailboxSubscriptionObserver;
    $observer->updated($subscription);
    // no-op, just ensure no exception
    expect(true)->toBeTrue();
});

it('handles restored event', function (): void {
    $subscription = MailboxSubscription::withoutBroadcasting(fn () => Mailbox::factory()->create()->subscriptions()->create([
        'external_id' => 'test-sub',
        'resource' => '/users/test/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    $observer = new MailboxSubscriptionObserver;
    $observer->restored($subscription);
    // no-op, just ensure no exception
    expect(true)->toBeTrue();
});

it('handles forceDeleted event', function (): void {
    $subscription = MailboxSubscription::withoutBroadcasting(fn () => Mailbox::factory()->create()->subscriptions()->create([
        'external_id' => 'test-sub',
        'resource' => '/users/test/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    $observer = new MailboxSubscriptionObserver;
    $observer->forceDeleted($subscription);
    // no-op, just ensure no exception
    expect(true)->toBeTrue();
});
