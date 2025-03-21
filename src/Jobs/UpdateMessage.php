<?php

namespace Actengage\Mailbox\Jobs;

use Actengage\Mailbox\Facades\Attachments;
use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Microsoft\Graph\Generated\Models\Message;

class UpdateMessage implements ShouldQueue
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

        Messages::find($this->mailbox->email, $this->id)->then(function(Message $message) {
            Messages::save($this->mailbox, $message);
        });
    }
}
