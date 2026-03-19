<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Illuminate\Support\Carbon;

it('belongs to a mailbox', function (): void {
    $mailbox = Mailbox::factory()->create();

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'sub-1',
        'resource' => '/users/test@test.com/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    expect($subscription->mailbox->is($mailbox))->toBeTrue();
});

it('scopes by expires_at', function (): void {
    $mailbox = Mailbox::factory()->create();

    $expiring = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'expiring',
        'resource' => '/users/test@test.com/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->subHour(),
    ]));

    $notExpiring = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'active',
        'resource' => '/users/test@test.com/folders',
        'change_type' => 'updated',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addWeek(),
    ]));

    $results = $mailbox->subscriptions()->expiresAt(now())->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->is($expiring))->toBeTrue();
});

it('casts expires_at to datetime', function (): void {
    $mailbox = Mailbox::factory()->create();

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'sub-dt',
        'resource' => '/users/test@test.com/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => '2025-06-15 12:00:00',
    ]));

    expect($subscription->expires_at)->toBeInstanceOf(Carbon::class);
});
