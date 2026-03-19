<?php

declare(strict_types=1);

namespace Tests;

use Actengage\Mailbox\ServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app->make(Repository::class)->set('mailbox.default', 'testing');
        $app->make(Repository::class)->set('mailbox.connections.testing', [
            'tenant_id' => 'test-tenant',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'scopes' => null,
            'webhook_host' => null,
        ]);
    }
}
