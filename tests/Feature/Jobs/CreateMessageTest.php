<?php

use Actengage\Mailbox\Facades\Attachments;
use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Jobs\CreateMessage;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;
use Http\Promise\FulfilledPromise;
use Microsoft\Graph\Generated\Models\Message;

it('finds the message, saves it, and processes url attachments', function (): void {
    $mailbox = Mailbox::factory()->create();
    $graphMessage = new Message;
    $graphMessage->setId('ext-id');
    $graphMessage->setSubject('Test Subject');

    $model = MailboxMessage::factory()->for($mailbox)->create();

    Client::shouldReceive('connect')->with($mailbox->connection)->once();

    Messages::shouldReceive('find')
        ->with($mailbox->email, 'ext-id')
        ->andReturn(new FulfilledPromise($graphMessage));

    Messages::shouldReceive('save')
        ->with($mailbox, $graphMessage)
        ->andReturn($model);

    Attachments::shouldReceive('processUrlsAsAttachments')
        ->with($model)
        ->once();

    new CreateMessage($mailbox, 'ext-id')->handle();
});

it('handles null message from promise', function (): void {
    $mailbox = Mailbox::factory()->create();

    Client::shouldReceive('connect')->with($mailbox->connection)->once();

    Messages::shouldReceive('find')
        ->with($mailbox->email, 'ext-id')
        ->andReturn(new FulfilledPromise(null));

    Messages::shouldReceive('save')->never();
    Attachments::shouldReceive('processUrlsAsAttachments')->never();

    new CreateMessage($mailbox, 'ext-id')->handle();
});
