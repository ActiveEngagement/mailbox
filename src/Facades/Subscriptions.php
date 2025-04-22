<?php

namespace Actengage\Mailbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actengage\Mailbox\Services\SubscriptionService
 * @method static void subscribe(\Actengage\Mailbox\Models\Mailbox $mailbox)
 * @method static \Http\Promise\Promise<void|null> delete(\Actengage\Mailbox\Models\MailboxSubscription|string $subscription)
 */
class Subscriptions extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox.subscriptions';
    }
}