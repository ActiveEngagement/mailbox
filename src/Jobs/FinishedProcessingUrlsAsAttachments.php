<?php

namespace Actengage\Mailbox\Jobs;

use Actengage\Mailbox\Events\ProcessedUrlsAsAttachments;
use Actengage\Mailbox\Models\MailboxMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinishedProcessingUrlsAsAttachments implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MailboxMessage $model
    ) {
        //
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        event(new ProcessedUrlsAsAttachments($this->model));
    }
}
