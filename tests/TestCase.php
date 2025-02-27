<?php

namespace Tests;

use Actengage\Mailbox\ServiceProvider;
use Actengage\Mailbox\Services\FolderService;
use Actengage\Mailbox\Services\MessageService;
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
