<?php

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Http\Promise\FulfilledPromise;

it('creates subscriptions for all mailboxes', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'sub@test.com']);

    Client::shouldReceive('connect')->once();
    Subscriptions::shouldReceive('subscribe')
        ->once()
        ->withArgs(fn (Mailbox $m): bool => $m->is($mailbox));

    $this->artisan('mailbox:subscribe')
        ->expectsOutputToContain('sub@test.com have been resubscribed!')
        ->assertExitCode(0);
});

it('creates subscriptions for a specific mailbox by email', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'specific@test.com']);
    Mailbox::factory()->create(['email' => 'other@test.com']);

    Client::shouldReceive('connect')->once();
    Subscriptions::shouldReceive('subscribe')
        ->once()
        ->withArgs(fn (Mailbox $m): bool => $m->is($mailbox));

    $this->artisan('mailbox:subscribe', ['--email' => 'specific@test.com'])
        ->assertExitCode(0);
});

it('deletes existing subscriptions before resubscribing', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'resub@test.com']);

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'old-sub',
        'resource' => '/users/resub@test.com/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    Subscriptions::shouldReceive('delete')
        ->andReturn(new FulfilledPromise(null));

    Client::shouldReceive('connect')->once();
    Subscriptions::shouldReceive('subscribe')->once();

    $this->artisan('mailbox:subscribe')
        ->assertExitCode(0);

    expect(MailboxSubscription::find($subscription->id))->toBeNull();
});
