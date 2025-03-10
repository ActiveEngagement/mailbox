<?php

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Http\Promise\Promise;
use Illuminate\Support\Uri;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
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
     *
     * @param Mailbox $mailbox
     * @return void
     */
    public function subscribe(Mailbox $mailbox): void
    {
        $this->subscribeToFolders($mailbox);
        $this->subscribeToMessages($mailbox);
    }

    /**
     * Subscripe to created, updated, deleted message changes.
     *
     * @param Mailbox $mailbox
     * @return Promise<MailboxSubscription|null>
     */
    protected function subscribeToFolders(Mailbox $mailbox): Promise
    {
        $subscription = new Subscription();
        $subscription->setChangeType('updated,deleted');
        $subscription->setNotificationUrl($this->notificationUrl($mailbox, 'mailbox.webhooks.folders'));
        $subscription->setResource("/users/$mailbox->email/mailFolders");
        $subscription->setExpirationDateTime(now()->addDays(1));
        
        return $this->service->client()
            ->subscriptions()
            ->post($subscription)
            ->then(function($subscription) use ($mailbox) {
                if(!$subscription) {
                    return;
                }

                return $this->createMailboxSubscription($mailbox, $subscription);
            });
    }

    /**
     * Subscripe to created, updated, deleted message changes.
     *
     * @param Mailbox $mailbox
     * @return Promise<MailboxSubscription|null>
     */
    protected function subscribeToMessages(Mailbox $mailbox): Promise
    {
        $subscription = new Subscription();
        $subscription->setChangeType('created,updated,deleted');
        $subscription->setNotificationUrl($this->notificationUrl($mailbox, 'mailbox.webhooks.messages'));
        $subscription->setResource("/users/$mailbox->email/messages");
        $subscription->setExpirationDateTime(now()->addDays(1));
        
        return $this->service->client()
            ->subscriptions()
            ->post($subscription)
            ->then(function($subscription) use ($mailbox) {
                if(!$subscription) {
                    return;
                }

                return $this->createMailboxSubscription($mailbox, $subscription);
            });
    }

    /**
     * Create the MailboxSubscription model from the given Subscription.
     *
     * @param Mailbox $mailbox
     * @param Subscription $subscription
     * @return MailboxSubscription
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
     *
     * @param Mailbox $mailbox
     * @param string $route
     * @return Uri
     */
    protected function notificationUrl(Mailbox $mailbox, string $route): string
    {
        extract(parse_url(Client::config('webhook_host', config('app.url'))));

        return Uri::route($route, ['mailbox' => $mailbox], false)
            ->withHost(isset($host) ? $host : 'localhost')
            ->withScheme(isset($scheme) ? $scheme : 'https');
    }

    /**
     * Delete the subscription.
     *
     * @param MailboxSubscription|string $subscription
     * @return void
     */
    public function delete(MailboxSubscription|string $subscription): void
    {
        $id = $subscription instanceof MailboxSubscription
            ? $subscription->getKey()
            : $subscription;

        $this->service->client()
            ->subscriptions()
            ->bySubscriptionId($id)
            ->delete()
            ->then(null, function() {
                //
            });
    }
}