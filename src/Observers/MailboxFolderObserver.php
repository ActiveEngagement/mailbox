<?php

namespace Actengage\Mailbox\Observers;

use Actengage\Mailbox\Models\MailboxFolder;
use Illuminate\Support\Facades\Cache;

class MailboxFolderObserver
{
    /**
     * Handle the MailboxFolder "created" event.
     */
    public function created(MailboxFolder $mailboxFolder): void
    {
        $this->bustCache($mailboxFolder);
    }

    /**
     * Handle the MailboxFolder "updated" event.
     */
    public function updated(MailboxFolder $mailboxFolder): void
    {
        $this->bustCache($mailboxFolder);
    }

    /**
     * Handle the MailboxFolder "deleted" event.
     */
    public function deleted(MailboxFolder $mailboxFolder): void
    {
        $this->bustCache($mailboxFolder);
    }

    /**
     * Handle the MailboxFolder "restored" event.
     */
    public function restored(MailboxFolder $mailboxFolder): void
    {
        $this->bustCache($mailboxFolder);
    }

    /**
     * Handle the MailboxFolder "force deleted" event.
     */
    public function forceDeleted(MailboxFolder $mailboxFolder): void
    {
        $this->bustCache($mailboxFolder);
    }

    /**
     * Bust the cache folder cache.
     *
     * @param MailboxFolder $mailboxFolder
     * @return void
     */
    protected function bustCache(MailboxFolder $mailboxFolder): void
    {
        Cache::forget("mailbox.{$mailboxFolder->mailbox->id}.folders.drafts");
        Cache::forget("mailbox.{$mailboxFolder->mailbox->id}.folders.sentItems");
    }
}
