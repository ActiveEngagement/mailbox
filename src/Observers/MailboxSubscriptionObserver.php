<?php

namespace Actengage\Mailbox\Observers;

use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\MailboxSubscription;

class MailboxSubscriptionObserver
{
    /**
     * Handle the MailboxSubscription "created" event.
     */
    public function created(MailboxSubscription $subscription): void
    {
        //
    }

    /**
     * Handle the MailboxSubscription "updated" event.
     */
    public function updated(MailboxSubscription $subscription): void
    {
        //
    }

    /**
     * Handle the MailboxSubscription "deleted" event.
     */
    public function deleted(MailboxSubscription $subscription): void
    {
        Subscriptions::delete($subscription->external_id);
    }

    /**
     * Handle the MailboxSubscription "restored" event.
     */
    public function restored(MailboxSubscription $subscription): void
    {
        //
    }

    /**
     * Handle the MailboxSubscription "force deleted" event.
     */
    public function forceDeleted(MailboxSubscription $subscription): void
    {
        //
    }
}
