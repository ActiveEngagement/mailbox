<?php

namespace Actengage\Mailbox\Services;

use Illuminate\Support\Arr;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Graph\GraphServiceClient;

class ClientService
{
    /**
     * The client credential context.
     *
     * @var ClientCredentialContext
     */
    protected ClientCredentialContext $credentials;

    /**
     * The graph service client.
     *
     * @var GraphServiceClient
     */
    protected GraphServiceClient $client;

    /**
     * The name of the connection.
     *
     * @var string
     */
    protected string $connection;

    /**
     * The configuration for the given connection.
     *
     * @var array
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
     *
     * @param string|null $connection
     */
    public function __construct(?string $connection = null)
    {
        $this->connect($connection);
    }

    /**
     * Get the current ClientCredentialContext.
     *
     * @return ClientCredentialContext
     */
    public function credentials(): ClientCredentialContext
    {
        return $this->credentials;
    }

    /**
     * Get the current GraphServiceClient.
     *
     * @return GraphServiceClient
     */
    public function client(): GraphServiceClient
    {
        return $this->client;
    }

    /**
     * Get the config for the current connection.
     *
     * @return array<string,string>|string|null
     */
    public function config(?string $key = null, ?string $default = null): array|string|null
    {
        if($key) {
            return Arr::get($this->config, $key, $default);
        }

        return $this->config;
    }

    /**
     * Get the name of the current connection.
     *
     * @return string
     */
    public function connection(): string
    {
        return $this->connection;
    }

    /**
     * Use the given connection.
     *
     * @param string|null $connection
     * @return GraphServiceClient
     */
    public function connect(?string $connection = null): GraphServiceClient
    {
        $config = $this->withConfig($connection);
        
        $this->credentials = new ClientCredentialContext(
            $config['tenant_id'], $config['client_id'], $config['client_secret']
        );
        
        $this->client = new GraphServiceClient($this->credentials);

        return $this->client;
    }

    /**
     * Set and return the config from the name of the connection.
     *
     * @param string|null $connection
     * @return array
     */
    protected function withConfig(?string $connection = null): array
    {
        $this->connection = $connection ?? config('mailbox.default');
        
        $credentials = config('mailbox.connections.' . (
            $connection ?? config('mailbox.default')
        ));

        return $this->config = array_merge(
            $this->config, $credentials
        );
    }
}