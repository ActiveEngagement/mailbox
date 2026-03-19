<?php

use Actengage\Mailbox\Services\ClientService;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;

it('creates a client on construction', function (): void {
    $service = new ClientService;

    expect($service->client())->toBeInstanceOf(GraphServiceClient::class);
    expect($service->credentials())->toBeInstanceOf(ClientCredentialContext::class);
    expect($service->connection())->toBe('testing');
});

it('returns config array when no key provided', function (): void {
    $service = new ClientService;
    $config = $service->config();

    expect($config)->toBeArray();
    expect($config['tenant_id'])->toBe('test-tenant');
    expect($config['client_id'])->toBe('test-client');
});

it('returns config value by key', function (): void {
    $service = new ClientService;

    expect($service->config('tenant_id'))->toBe('test-tenant');
    expect($service->config('client_id'))->toBe('test-client');
    expect($service->config('nonexistent', 'default'))->toBe('default');
});

it('connects to a specific connection', function (): void {
    config()->set('mailbox.connections.other', [
        'tenant_id' => 'other-tenant',
        'client_id' => 'other-client',
        'client_secret' => 'other-secret',
        'scopes' => null,
        'webhook_host' => null,
    ]);

    $service = new ClientService('other');

    expect($service->connection())->toBe('other');
    expect($service->config('tenant_id'))->toBe('other-tenant');
});

it('reconnects to a different connection', function (): void {
    config()->set('mailbox.connections.other', [
        'tenant_id' => 'other-tenant',
        'client_id' => 'other-client',
        'client_secret' => 'other-secret',
        'scopes' => null,
        'webhook_host' => null,
    ]);

    $service = new ClientService;
    expect($service->connection())->toBe('testing');

    $client = $service->connect('other');
    expect($client)->toBeInstanceOf(GraphServiceClient::class);
    expect($service->connection())->toBe('other');
    expect($service->config('tenant_id'))->toBe('other-tenant');
});

it('returns null for missing config key without default', function (): void {
    $service = new ClientService;

    expect($service->config('nonexistent_key'))->toBeNull();
});

it('merges config with defaults preserving all keys', function (): void {
    $service = new ClientService;
    $config = $service->config();

    expect($config)->toHaveKeys(['tenant_id', 'client_id', 'client_secret', 'scopes', 'webhook_host']);
    expect($config['scopes'])->toBeNull();
    expect($config['webhook_host'])->toBeNull();
    expect($config['client_secret'])->toBe('test-secret');
});

it('returns a new GraphServiceClient on connect', function (): void {
    $service = new ClientService;
    $client1 = $service->client();

    $client2 = $service->connect();

    expect($client2)->toBeInstanceOf(GraphServiceClient::class);
});

it('uses default connection when null is passed to constructor', function (): void {
    $service = new ClientService;

    expect($service->connection())->toBe('testing');
    expect($service->config('tenant_id'))->toBe('test-tenant');
});

it('uses default connection when null is passed to connect', function (): void {
    $service = new ClientService;
    $service->connect();

    expect($service->connection())->toBe('testing');
});
