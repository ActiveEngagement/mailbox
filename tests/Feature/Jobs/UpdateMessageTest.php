<?php

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Jobs\UpdateMessage;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;
use Http\Promise\FulfilledPromise;
use Microsoft\Graph\Generated\Models\Message;

it('finds and saves the message', function (): void {
    $mailbox = Mailbox::factory()->create();
    $graphMessage = new Message;
    $graphMessage->setId('ext-id');

    $model = MailboxMessage::factory()->for($mailbox)->create();

    Client::shouldReceive('connect')->with($mailbox->connection)->once();

    Messages::shouldReceive('find')
        ->with($mailbox->email, 'ext-id')
        ->andReturn(new FulfilledPromise($graphMessage));

    Messages::shouldReceive('save')
        ->with($mailbox, $graphMessage)
        ->andReturn($model);

    new UpdateMessage($mailbox, 'ext-id')->handle();
});

it('handles null message from promise', function (): void {
    $mailbox = Mailbox::factory()->create();

    Client::shouldReceive('connect')->with($mailbox->connection)->once();

    Messages::shouldReceive('find')
        ->with($mailbox->email, 'ext-id')
        ->andReturn(new FulfilledPromise(null));

    Messages::shouldReceive('save')->never();

    new UpdateMessage($mailbox, 'ext-id')->handle();
});
