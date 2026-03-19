<?php

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Actengage\Mailbox\Services\ClientService;
use Actengage\Mailbox\Services\SubscriptionService;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Microsoft\Graph\Generated\Models\Subscription;
use Microsoft\Graph\Generated\Subscriptions\Item\SubscriptionItemRequestBuilder;
use Microsoft\Graph\Generated\Subscriptions\SubscriptionsRequestBuilder;
use Microsoft\Graph\GraphServiceClient;

it('subscribes to folders and messages', function (): void {
    $mailbox = Mailbox::factory()->create();

    $folderSubscription = new Subscription;
    $folderSubscription->setId('folder-sub-id');
    $folderSubscription->setResource(sprintf('/users/%s/mailFolders', $mailbox->email));
    $folderSubscription->setChangeType('updated,deleted');
    $folderSubscription->setNotificationUrl('https://example.com/folders');
    $folderSubscription->setExpirationDateTime(now()->addDay());

    $messageSubscription = new Subscription;
    $messageSubscription->setId('message-sub-id');
    $messageSubscription->setResource(sprintf('/users/%s/messages', $mailbox->email));
    $messageSubscription->setChangeType('created,updated,deleted');
    $messageSubscription->setNotificationUrl('https://example.com/messages');
    $messageSubscription->setExpirationDateTime(now()->addDay());

    $folderPromise = new FulfilledPromise($folderSubscription);
    $messagePromise = new FulfilledPromise($messageSubscription);

    $subscriptionsBuilder = Mockery::mock(SubscriptionsRequestBuilder::class);
    $subscriptionsBuilder->shouldReceive('post')
        ->twice()
        ->andReturn($folderPromise, $messagePromise);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('subscriptions')->twice()->andReturn($subscriptionsBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new SubscriptionService($clientService);
    $service->subscribe($mailbox);

    expect(MailboxSubscription::count())->toBe(2);
    expect(MailboxSubscription::where('external_id', 'folder-sub-id')->exists())->toBeTrue();
    expect(MailboxSubscription::where('external_id', 'message-sub-id')->exists())->toBeTrue();
});

it('handles non-Subscription response in subscribe callback', function (): void {
    $mailbox = Mailbox::factory()->create();

    $promise = new FulfilledPromise(null);

    $subscriptionsBuilder = Mockery::mock(SubscriptionsRequestBuilder::class);
    $subscriptionsBuilder->shouldReceive('post')
        ->twice()
        ->andReturn($promise);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('subscriptions')->twice()->andReturn($subscriptionsBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new SubscriptionService($clientService);
    $service->subscribe($mailbox);

    expect(MailboxSubscription::count())->toBe(0);
});

it('deletes a subscription by model', function (): void {
    $mailbox = Mailbox::factory()->create();

    $subscription = MailboxSubscription::withoutBroadcasting(fn () => $mailbox->subscriptions()->create([
        'external_id' => 'sub-to-delete',
        'resource' => '/users/test/messages',
        'change_type' => 'created',
        'notification_url' => 'https://example.com/webhook',
        'expires_at' => now()->addDay(),
    ]));

    $promise = new FulfilledPromise(null);

    $subscriptionItemBuilder = Mockery::mock(SubscriptionItemRequestBuilder::class);
    $subscriptionItemBuilder->shouldReceive('delete')->once()->andReturn($promise);

    $subscriptionsBuilder = Mockery::mock(SubscriptionsRequestBuilder::class);
    $subscriptionsBuilder->shouldReceive('bySubscriptionId')->with('sub-to-delete')->once()->andReturn($subscriptionItemBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('subscriptions')->once()->andReturn($subscriptionsBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new SubscriptionService($clientService);
    $result = $service->delete($subscription);

    expect($result)->toBeInstanceOf(Promise::class);
});

it('deletes a subscription by string id', function (): void {
    $promise = new FulfilledPromise(null);

    $subscriptionItemBuilder = Mockery::mock(SubscriptionItemRequestBuilder::class);
    $subscriptionItemBuilder->shouldReceive('delete')->once()->andReturn($promise);

    $subscriptionsBuilder = Mockery::mock(SubscriptionsRequestBuilder::class);
    $subscriptionsBuilder->shouldReceive('bySubscriptionId')->with('string-sub-id')->once()->andReturn($subscriptionItemBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('subscriptions')->once()->andReturn($subscriptionsBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new SubscriptionService($clientService);
    $result = $service->delete('string-sub-id');

    expect($result->wait())->toBeNull();
});

it('creates correct subscription resources for folders and messages', function (): void {
    $mailbox = Mailbox::factory()->create(['email' => 'inbox@example.com']);

    $capturedSubscriptions = [];

    $subscriptionsBuilder = Mockery::mock(SubscriptionsRequestBuilder::class);
    $subscriptionsBuilder->shouldReceive('post')
        ->twice()
        ->andReturnUsing(function (Subscription $sub) use (&$capturedSubscriptions) {
            $capturedSubscriptions[] = $sub;

            $response = new Subscription;
            $response->setId('sub-'.count($capturedSubscriptions));
            $response->setResource($sub->getResource());
            $response->setChangeType($sub->getChangeType());
            $response->setNotificationUrl($sub->getNotificationUrl());
            $response->setExpirationDateTime($sub->getExpirationDateTime());

            return new FulfilledPromise($response);
        });

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('subscriptions')->twice()->andReturn($subscriptionsBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new SubscriptionService($clientService);
    $service->subscribe($mailbox);

    expect($capturedSubscriptions)->toHaveCount(2);
    expect($capturedSubscriptions[0]->getResource())->toBe('/users/inbox@example.com/mailFolders');
    expect($capturedSubscriptions[0]->getChangeType())->toBe('updated,deleted');
    expect($capturedSubscriptions[1]->getResource())->toBe('/users/inbox@example.com/messages');
    expect($capturedSubscriptions[1]->getChangeType())->toBe('created,updated,deleted');

    expect(MailboxSubscription::count())->toBe(2);
});

it('uses webhook_host from config for notification url', function (): void {
    config()->set('mailbox.default', 'testing');
    config()->set('mailbox.connections.testing', [
        'tenant_id' => 'test-tenant',
        'client_id' => 'test-client',
        'client_secret' => 'test-secret',
        'scopes' => null,
        'webhook_host' => 'https://webhook.example.com',
    ]);

    $mailbox = Mailbox::factory()->create(['email' => 'inbox@example.com']);

    $capturedSubscriptions = [];

    $subscriptionsBuilder = Mockery::mock(SubscriptionsRequestBuilder::class);
    $subscriptionsBuilder->shouldReceive('post')
        ->twice()
        ->andReturnUsing(function (Subscription $sub) use (&$capturedSubscriptions) {
            $capturedSubscriptions[] = $sub;

            $response = new Subscription;
            $response->setId('sub-'.count($capturedSubscriptions));
            $response->setResource($sub->getResource());
            $response->setChangeType($sub->getChangeType());
            $response->setNotificationUrl($sub->getNotificationUrl());
            $response->setExpirationDateTime($sub->getExpirationDateTime());

            return new FulfilledPromise($response);
        });

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('subscriptions')->twice()->andReturn($subscriptionsBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    // We need to use the Client facade for the notificationUrl method
    Client::shouldReceive('config')
        ->with('webhook_host', Mockery::any())
        ->andReturn('https://webhook.example.com');

    $service = new SubscriptionService($clientService);
    $service->subscribe($mailbox);

    expect($capturedSubscriptions)->toHaveCount(2);
    expect($capturedSubscriptions[0]->getNotificationUrl())->toContain('webhook.example.com');
    expect($capturedSubscriptions[1]->getNotificationUrl())->toContain('webhook.example.com');
});

it('creates mailbox subscription with correct attributes', function (): void {
    $mailbox = Mailbox::factory()->create();

    $folderSub = new Subscription;
    $folderSub->setId('sub-123');
    $folderSub->setResource('/users/test/mailFolders');
    $folderSub->setChangeType('updated,deleted');
    $folderSub->setNotificationUrl('https://example.com/webhook/folders');
    $folderSub->setExpirationDateTime(now()->addDay());

    $messageSub = new Subscription;
    $messageSub->setId('sub-456');
    $messageSub->setResource('/users/test/messages');
    $messageSub->setChangeType('created,updated,deleted');
    $messageSub->setNotificationUrl('https://example.com/webhook/messages');
    $messageSub->setExpirationDateTime(now()->addDay());

    $subscriptionsBuilder = Mockery::mock(SubscriptionsRequestBuilder::class);
    $subscriptionsBuilder->shouldReceive('post')
        ->twice()
        ->andReturn(
            new FulfilledPromise($folderSub),
            new FulfilledPromise($messageSub)
        );

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('subscriptions')->twice()->andReturn($subscriptionsBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new SubscriptionService($clientService);
    $service->subscribe($mailbox);

    $folderModel = MailboxSubscription::where('external_id', 'sub-123')->first();
    expect($folderModel)->not->toBeNull();
    expect($folderModel->resource)->toBe('/users/test/mailFolders');
    expect($folderModel->change_type)->toBe('updated,deleted');
    expect($folderModel->notification_url)->toBe('https://example.com/webhook/folders');
    expect($folderModel->mailbox_id)->toBe($mailbox->id);

    $messageModel = MailboxSubscription::where('external_id', 'sub-456')->first();
    expect($messageModel)->not->toBeNull();
    expect($messageModel->resource)->toBe('/users/test/messages');
    expect($messageModel->change_type)->toBe('created,updated,deleted');
});
