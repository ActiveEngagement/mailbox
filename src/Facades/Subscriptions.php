<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Facades;

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxSubscription;
use Actengage\Mailbox\Services\SubscriptionService;
use Http\Promise\Promise;
use Illuminate\Support\Facades\Facade;

/**
 * @see SubscriptionService
 *
 * @method static void subscribe(Mailbox $mailbox)
 * @method static Promise<void|null> delete(MailboxSubscription|string $subscription)
 */
class Subscriptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mailbox.subscriptions';
    }
}
