<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Services;

use Illuminate\Support\Arr;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;

class ClientService
{
    /**
     * The client credential context.
     */
    protected ClientCredentialContext $credentials;

    /**
     * The graph service client.
     */
    protected GraphServiceClient $client;

    /**
     * The name of the connection.
     */
    protected string $connection;

    /**
     * The configuration for the given connection.
     *
     * @var array<string, string|null>
     */
    protected array $config = [
        'tenant_id' => null,
        'client_id' => null,
        'client_secret' => null,
        'scopes' => null,
        'webhook_host' => null,
    ];

    /**
     * Create the client service.
     */
    public function __construct(?string $connection = null)
    {
        $this->connect($connection);
    }

    /**
     * Get the current ClientCredentialContext.
     */
    public function credentials(): ClientCredentialContext
    {
        return $this->credentials;
    }

    /**
     * Get the current GraphServiceClient.
     */
    public function client(): GraphServiceClient
    {
        return $this->client;
    }

    /**
     * Get the config for the current connection.
     *
     * @return array<string, string|null>|string|null
     */
    public function config(?string $key = null, ?string $default = null): array|string|null
    {
        if ($key) {
            /** @var string|null */
            return Arr::get($this->config, $key, $default);
        }

        return $this->config;
    }

    /**
     * Get the name of the current connection.
     */
    public function connection(): string
    {
        return $this->connection;
    }

    /**
     * Use the given connection.
     */
    public function connect(?string $connection = null): GraphServiceClient
    {
        $config = $this->withConfig($connection);

        $this->credentials = new ClientCredentialContext(
            (string) $config['tenant_id'], (string) $config['client_id'], (string) $config['client_secret']
        );

        $this->client = new GraphServiceClient($this->credentials);

        return $this->client;
    }

    /**
     * Set and return the config from the name of the connection.
     *
     * @return array<string, string|null>
     */
    protected function withConfig(?string $connection = null): array
    {
        /** @var string $defaultConnection */
        $defaultConnection = config('mailbox.default');
        $this->connection = $connection ?? $defaultConnection;

        /** @var array<string, string|null> $credentials */
        $credentials = config('mailbox.connections.'.($connection ?? $defaultConnection));

        return $this->config = array_merge(
            $this->config, $credentials
        );
    }
}
