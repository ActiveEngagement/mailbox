<?php

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Support\Collection;

it('sets up a mailbox with folders, messages, and subscriptions', function (): void {
    Client::shouldReceive('connection')->andReturn('default');

    Folders::shouldReceive('all')
        ->with('setup@test.com')
        ->andReturn(new Collection);

    Messages::shouldReceive('all')
        ->once();

    Subscriptions::shouldReceive('subscribe')->once();

    $this->artisan('mailbox:setup', ['email' => 'setup@test.com'])
        ->expectsOutputToContain('setup@test.com was setup!')
        ->assertExitCode(0);

    expect(Mailbox::query()->email('setup@test.com')->exists())->toBeTrue();
});

it('skips message creation when --skip flag is provided', function (): void {
    Client::shouldReceive('connection')->andReturn('default');

    Folders::shouldReceive('all')
        ->with('skip@test.com')
        ->andReturn(new Collection);

    Messages::shouldReceive('all')->never();

    Subscriptions::shouldReceive('subscribe')->once();

    $this->artisan('mailbox:setup', ['email' => 'skip@test.com', '--skip' => true])
        ->assertExitCode(0);
});

it('uses a date filter when --after option is provided', function (): void {
    Client::shouldReceive('connection')->andReturn('default');

    Folders::shouldReceive('all')
        ->with('after@test.com')
        ->andReturn(new Collection);

    Messages::shouldReceive('all')
        ->once()
        ->withArgs(function (string $email, callable $iterator, $filter) {
            return $email === 'after@test.com' && $filter !== null;
        });

    Subscriptions::shouldReceive('subscribe')->once();

    $this->artisan('mailbox:setup', ['email' => 'after@test.com', '--after' => '2025-01-01'])
        ->assertExitCode(0);
});

it('saves folders returned by the API', function (): void {
    $graphFolder = new \Microsoft\Graph\Generated\Models\MailFolder;
    $graphFolder->setId('folder-id');
    $graphFolder->setDisplayName('Inbox');

    Client::shouldReceive('connection')->andReturn('default');

    Folders::shouldReceive('all')
        ->with('folders@test.com')
        ->andReturn(new Collection([$graphFolder]));

    Folders::shouldReceive('save')
        ->once()
        ->withArgs(fn (Mailbox $mailbox, \Microsoft\Graph\Generated\Models\MailFolder $folder) => $folder->getId() === 'folder-id');

    Messages::shouldReceive('all')->once();

    Subscriptions::shouldReceive('subscribe')->once();

    $this->artisan('mailbox:setup', ['email' => 'folders@test.com'])
        ->assertExitCode(0);
});

it('saves messages returned by the API', function (): void {
    $graphMessage = new \Microsoft\Graph\Generated\Models\Message;
    $graphMessage->setId('msg-id');

    Client::shouldReceive('connection')->andReturn('default');

    Folders::shouldReceive('all')
        ->with('msgs@test.com')
        ->andReturn(new Collection);

    Messages::shouldReceive('all')
        ->once()
        ->withArgs(function (string $email, callable $iterator, $filter) use ($graphMessage) {
            $iterator($graphMessage);

            return $email === 'msgs@test.com';
        });

    Messages::shouldReceive('save')->once();

    Subscriptions::shouldReceive('subscribe')->once();

    $this->artisan('mailbox:setup', ['email' => 'msgs@test.com'])
        ->assertExitCode(0);
});
