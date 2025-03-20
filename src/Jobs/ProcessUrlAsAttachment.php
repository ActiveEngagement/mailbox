<?php

namespace Actengage\Mailbox\Jobs;

use Actengage\Mailbox\Events\ProcessedUrlsAsAttachments;
use Actengage\Mailbox\Facades\Attachments;
use Actengage\Mailbox\Models\MailboxMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessUrlAsAttachment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MailboxMessage $message,
        public string $url,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Attachments::get($this->url);

        if(!$response->header('Content-Disposition')) {
            return;
        }

        $disposition = Attachments::contentDisposition($response);

        if($disposition->getType() !== 'attachment') {
            return;
        }
        
        Attachments::createFromResponse(
            $this->message, $response, $disposition
        );
    }
}
