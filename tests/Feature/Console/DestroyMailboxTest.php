<?php

use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Http\Promise\FulfilledPromise;

it('destroys a mailbox and its subscriptions', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'destroy@test.com']);

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'sub-1',
        'resource' => '/users/destroy@test.com/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    Subscriptions::shouldReceive('delete')
        ->andReturn(new FulfilledPromise(null));

    $this->artisan('mailbox:destroy', ['email' => 'destroy@test.com'])
        ->expectsOutputToContain('destroy@test.com was destroyed!')
        ->assertExitCode(0);

    expect(Mailbox::find($mailbox->id))->toBeNull();
    expect(MailboxSubscription::find($subscription->id))->toBeNull();
});
