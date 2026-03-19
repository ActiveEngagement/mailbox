<?php

use Actengage\Mailbox\Services\AttachmentService;
use Actengage\Mailbox\Services\ClientService;
use Actengage\Mailbox\Services\FolderService;
use Actengage\Mailbox\Services\MessageService;
use Actengage\Mailbox\Services\ModelService;
use Actengage\Mailbox\Services\SubscriptionService;

it('registers the client service', function (): void {
    expect(resolve('mailbox.graph.client'))->toBeInstanceOf(ClientService::class);
    expect(resolve(ClientService::class))->toBeInstanceOf(ClientService::class);
});

it('registers the attachment service', function (): void {
    expect(resolve('mailbox.attachments'))->toBeInstanceOf(AttachmentService::class);
    expect(resolve(AttachmentService::class))->toBeInstanceOf(AttachmentService::class);
});

it('registers the folder service', function (): void {
    expect(resolve('mailbox.folders'))->toBeInstanceOf(FolderService::class);
    expect(resolve(FolderService::class))->toBeInstanceOf(FolderService::class);
});

it('registers the message service', function (): void {
    expect(resolve('mailbox.messages'))->toBeInstanceOf(MessageService::class);
    expect(resolve(MessageService::class))->toBeInstanceOf(MessageService::class);
});

it('registers the model service', function (): void {
    expect(resolve('mailbox.models'))->toBeInstanceOf(ModelService::class);
    expect(resolve(ModelService::class))->toBeInstanceOf(ModelService::class);
});

it('registers the subscription service', function (): void {
    expect(resolve('mailbox.subscriptions'))->toBeInstanceOf(SubscriptionService::class);
    expect(resolve(SubscriptionService::class))->toBeInstanceOf(SubscriptionService::class);
});

it('registers the console commands', function (): void {
    $this->artisan('list')
        ->expectsOutputToContain('mailbox:setup')
        ->expectsOutputToContain('mailbox:destroy')
        ->expectsOutputToContain('mailbox:subscribe')
        ->expectsOutputToContain('mailbox:resubscribe');
});

it('merges the mailbox config', function (): void {
    expect(config('mailbox.default'))->toBe('testing');
    expect(config('mailbox.connections'))->toBeArray();
    expect(config('mailbox.mailboxes'))->toBeArray();
});
