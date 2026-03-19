<?php

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Jobs\SaveFolder;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Http\Promise\FulfilledPromise;
use Microsoft\Graph\Generated\Models\MailFolder;

it('finds and saves the folder', function (): void {
    $mailbox = Mailbox::factory()->create();
    $graphFolder = new MailFolder;
    $graphFolder->setId('folder-ext-id');
    $graphFolder->setDisplayName('Inbox');

    $model = MailboxFolder::factory()->for($mailbox)->create();

    Client::shouldReceive('connect')->with($mailbox->connection)->once();

    Folders::shouldReceive('find')
        ->with($mailbox->email, 'folder-ext-id')
        ->andReturn(new FulfilledPromise($graphFolder));

    Folders::shouldReceive('save')
        ->with($mailbox, $graphFolder)
        ->andReturn($model);

    (new SaveFolder($mailbox, 'folder-ext-id'))->handle();
});

it('handles null folder from promise', function (): void {
    $mailbox = Mailbox::factory()->create();

    Client::shouldReceive('connect')->with($mailbox->connection)->once();

    Folders::shouldReceive('find')
        ->with($mailbox->email, 'folder-ext-id')
        ->andReturn(new FulfilledPromise(null));

    Folders::shouldReceive('save')->never();

    (new SaveFolder($mailbox, 'folder-ext-id'))->handle();
});

it('invokes rejection handler when promise is rejected', function (): void {
    $mailbox = Mailbox::factory()->create();

    Client::shouldReceive('connect')->with($mailbox->connection)->once();

    $exception = new \RuntimeException('Graph API error');

    Folders::shouldReceive('find')
        ->with($mailbox->email, 'folder-ext-id')
        ->andReturn(new \Http\Promise\RejectedPromise($exception));

    Folders::shouldReceive('save')->never();

    // The rejection handler throws, but the promise catches it internally
    (new SaveFolder($mailbox, 'folder-ext-id'))->handle();
});
