<?php

use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;

it('creates broadcastable events that exclude the current user', function (): void {
    $mailbox = Mailbox::factory()->make();

    $reflection = new ReflectionMethod($mailbox, 'newBroadcastableEvent');
    $event = $reflection->invoke($mailbox, 'created');

    expect($event)->toBeInstanceOf(BroadcastableModelEventOccurred::class);
});
