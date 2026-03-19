<?php

declare(strict_types=1);

namespace Tests;

use Actengage\Mailbox\ServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            LaravelDataServiceProvider::class,
            // MessageService::class,
            // FolderService::class,
        ];
    }
}
