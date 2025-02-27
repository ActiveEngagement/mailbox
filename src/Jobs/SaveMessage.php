<?php

namespace Actengage\Mailbox\Jobs;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SaveMessage implements ShouldQueue
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
        Client::connect($this->mailbox->connection);

        $message = Messages::find($this->mailbox->email, $this->id);

        Messages::save($this->mailbox, $message);
    }
}
