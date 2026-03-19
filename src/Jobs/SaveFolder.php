<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Jobs;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Microsoft\Graph\Generated\Models\MailFolder;
use Throwable;

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

        Folders::find($this->mailbox->email, $this->id)->then(function (?MailFolder $folder): void {
            if (! $folder instanceof MailFolder) {
                return;
            }

            Folders::save($this->mailbox, $folder);
        }, function (Throwable $e): void {
            throw $e;
        });
    }
}
