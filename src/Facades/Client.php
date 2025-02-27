<?php

namespace Actengage\Mailbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actengage\Mailbox\Services\ClientService
 * @method static array<string,string>|string|null config(?string $key = null, ?string $default = null)
 * @method static string connection()
 * @method static \Microsoft\Graph\GraphServiceClient connect(?string $connection = null)
 * @method static \Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext credentials()
 * @method static \Microsoft\Graph\GraphServiceClient client()
 */
class Client extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox.graph.client';
    }
}