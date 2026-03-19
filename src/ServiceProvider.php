<?php

declare(strict_types=1);

namespace Actengage\Mailbox;

use Actengage\Mailbox\Console\CreateSubscriptions;
use Actengage\Mailbox\Console\DestroyMailbox;
use Actengage\Mailbox\Console\RenewSubscriptions;
use Actengage\Mailbox\Console\SetupMailbox;
use Actengage\Mailbox\Services\AttachmentService;
use Actengage\Mailbox\Services\ClientService;
use Actengage\Mailbox\Services\FolderService;
use Actengage\Mailbox\Services\MessageService;
use Actengage\Mailbox\Services\ModelService;
use Actengage\Mailbox\Services\SubscriptionService;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Override;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/mailbox.php', 'mailbox'
        );

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');

        $this->registerClientService();
        $this->registerAttachmentService();
        $this->registerFolderService();
        $this->registerMessageService();
        $this->registerModelService();
        $this->registerSubscriptionService();
    }

    /**
     * Boot any application services.
     */
    public function boot(): void
    {
        AboutCommand::add('Mailbox', fn (): array => ['Version' => 'v1.0.0']);

        $this->publishes([
            __DIR__.'/../config/mailbox.php' => config_path('mailbox.php'),
        ], 'mailbox-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mailbox-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SetupMailbox::class,
                DestroyMailbox::class,
                CreateSubscriptions::class,
                RenewSubscriptions::class,
            ]);
        }
    }

    /**
     * Register the ClientCredentialContext service.
     */
    protected function registerClientService(): void
    {
        $this->app->singleton(ClientService::class, fn (): ClientService => new ClientService);

        $this->app->alias(ClientService::class, 'mailbox.graph.client');
    }

    /**
     * Register the Attachment service.
     */
    protected function registerAttachmentService(): void
    {
        $this->app->singleton(AttachmentService::class, fn (): AttachmentService => new AttachmentService(resolve(ClientService::class)));

        $this->app->alias(AttachmentService::class, 'mailbox.attachments');
    }

    /**
     * Register the folder service.
     */
    protected function registerFolderService(): void
    {
        $this->app->singleton(FolderService::class, fn (): FolderService => new FolderService(resolve(ClientService::class)));

        $this->app->alias(FolderService::class, 'mailbox.folders');
    }

    /**
     * Register the message service.
     */
    protected function registerMessageService(): void
    {
        $this->app->singleton(MessageService::class, fn (): MessageService => new MessageService(resolve(ClientService::class)));

        $this->app->alias(MessageService::class, 'mailbox.messages');
    }

    /**
     * Register the mdoel service.
     */
    protected function registerModelService(): void
    {
        $this->app->singleton(ModelService::class, fn (): ModelService => new ModelService);

        $this->app->alias(ModelService::class, 'mailbox.models');
    }

    /**
     * Register the subscription service.
     */
    protected function registerSubscriptionService(): void
    {
        $this->app->singleton(SubscriptionService::class, fn (): SubscriptionService => new SubscriptionService(resolve(ClientService::class)));

        $this->app->alias(SubscriptionService::class, 'mailbox.subscriptions');
    }
}
