<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Http\Promise\Promise;
use Illuminate\Support\Uri;
use Microsoft\Graph\Generated\Models\Subscription;

class SubscriptionService
{
    public function __construct(
        protected ClientService $service
    ) {
        //
    }

    /**
     * Create all the subscriptions for a given mailbox.
     */
    public function subscribe(Mailbox $mailbox): void
    {
        $this->subscribeToFolders($mailbox);
        $this->subscribeToMessages($mailbox);
    }

    /**
     * Subscripe to created, updated, deleted message changes.
     *
     * @return Promise<MailboxSubscription|null>
     */
    protected function subscribeToFolders(Mailbox $mailbox): Promise
    {
        $subscription = new Subscription;
        $subscription->setChangeType('updated,deleted');
        $subscription->setNotificationUrl($this->notificationUrl($mailbox, 'mailbox.webhooks.folders'));
        $subscription->setResource(sprintf('/users/%s/mailFolders', $mailbox->email));
        $subscription->setExpirationDateTime(now()->addDays(1));

        return $this->service->client()
            ->subscriptions()
            ->post($subscription)
            ->then(function (mixed $subscription) use ($mailbox): ?MailboxSubscription {
                if (! $subscription instanceof Subscription) {
                    return null;
                }

                return $this->createMailboxSubscription($mailbox, $subscription);
            });
    }

    /**
     * Subscripe to created, updated, deleted message changes.
     *
     * @return Promise<MailboxSubscription|null>
     */
    protected function subscribeToMessages(Mailbox $mailbox): Promise
    {
        $subscription = new Subscription;
        $subscription->setChangeType('created,updated,deleted');
        $subscription->setNotificationUrl($this->notificationUrl($mailbox, 'mailbox.webhooks.messages'));
        $subscription->setResource(sprintf('/users/%s/messages', $mailbox->email));
        $subscription->setExpirationDateTime(now()->addDays(1));

        return $this->service->client()
            ->subscriptions()
            ->post($subscription)
            ->then(function (mixed $subscription) use ($mailbox): ?MailboxSubscription {
                if (! $subscription instanceof Subscription) {
                    return null;
                }

                return $this->createMailboxSubscription($mailbox, $subscription);
            });
    }

    /**
     * Create the MailboxSubscription model from the given Subscription.
     */
    protected function createMailboxSubscription(Mailbox $mailbox, Subscription $subscription): MailboxSubscription
    {
        return $mailbox->subscriptions()->create([
            'external_id' => $subscription->getId(),
            'resource' => $subscription->getResource(),
            'change_type' => $subscription->getChangeType(),
            'notification_url' => $subscription->getNotificationUrl(),
            'expires_at' => $subscription->getExpirationDateTime(),
        ]);
    }

    /**
     * Get the webhook notifcation url.
     */
    protected function notificationUrl(Mailbox $mailbox, string $route): string
    {
        $appUrl = config()->string('app.url');
        $webhookHost = Client::config('webhook_host', $appUrl);

        /** @var array{host?: string, scheme?: string} $parsed */
        $parsed = parse_url(is_string($webhookHost) ? $webhookHost : $appUrl);

        return (string) Uri::route($route, ['mailbox' => $mailbox], false)
            ->withHost($parsed['host'] ?? 'localhost')
            ->withScheme($parsed['scheme'] ?? 'https');
    }

    /**
     * Delete the subscription.
     *
     * @return Promise<void|null>
     */
    public function delete(MailboxSubscription|string $subscription): Promise
    {
        $id = $subscription instanceof MailboxSubscription
            ? (string) $subscription->external_id
            : $subscription;

        return $this->service->client()
            ->subscriptions()
            ->bySubscriptionId($id)
            ->delete();
    }
}
