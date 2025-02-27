<?php

namespace Actengage\Mailbox\Jobs;

use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mailbox $mailbox,
        public string $id,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->mailbox->messages()->externalId($this->id)->first()?->delete();
    }
}
