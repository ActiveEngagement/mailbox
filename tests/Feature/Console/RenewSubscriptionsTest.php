<?php

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Http\Promise\FulfilledPromise;

it('renews subscriptions that are expiring soon', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'renew@test.com']);

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'expiring-sub',
        'resource' => '/users/renew@test.com/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addMinutes(30),
    ]));

    Client::shouldReceive('connect')->once();
    Subscriptions::shouldReceive('subscribe')
        ->once()
        ->withArgs(fn (Mailbox $m): bool => $m->is($mailbox));
    Subscriptions::shouldReceive('delete')
        ->andReturn(new FulfilledPromise(null));

    $this->artisan('mailbox:resubscribe')
        ->expectsOutputToContain('renew@test.com have been resubscribed!')
        ->assertExitCode(0);

    expect(MailboxSubscription::find($subscription->id))->toBeNull();
});

it('warns when no subscriptions need renewal', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'norenew@test.com']);

    $this->artisan('mailbox:resubscribe')
        ->expectsOutputToContain('norenew@test.com has no subscriptions to renew!')
        ->assertExitCode(0);
});

it('filters by email when provided', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'filter@test.com']);
    Mailbox::factory()->create(['email' => 'other@test.com']);

    $this->artisan('mailbox:resubscribe', ['--email' => 'filter@test.com'])
        ->expectsOutputToContain('filter@test.com has no subscriptions to renew!')
        ->assertExitCode(0);
});
