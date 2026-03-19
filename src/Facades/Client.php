<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Facades;

use Actengage\Mailbox\Services\ClientService;
use Illuminate\Support\Facades\Facade;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;

/**
 * @see ClientService
 *
 * @method static array<string,string>|string|null config(?string $key = null, ?string $default = null)
 * @method static string connection()
 * @method static GraphServiceClient connect(?string $connection = null)
 * @method static ClientCredentialContext credentials()
 * @method static GraphServiceClient client()
 */
class Client extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mailbox.graph.client';
    }
}
