<?php

namespace Actengage\Mailbox\Jobs;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;

class SaveFolder implements ShouldQueue
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

        Folders::find($this->mailbox->email, $this->id)->then(function(MailFolder $folder) {
            Folders::save($this->mailbox, $folder);
        }, function(ODataError $e) {
            throw $e;
        });
    }
}
