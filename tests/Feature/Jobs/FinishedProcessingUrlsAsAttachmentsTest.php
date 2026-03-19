<?php

use Actengage\Mailbox\Events\ProcessedUrlsAsAttachments;
use Actengage\Mailbox\Jobs\FinishedProcessingUrlsAsAttachments;
use Actengage\Mailbox\Models\MailboxMessage;
use Illuminate\Support\Facades\Event;

it('dispatches the ProcessedUrlsAsAttachments event', function (): void {
    Event::fake();

    $message = MailboxMessage::factory()->create();

    (new FinishedProcessingUrlsAsAttachments($message))->handle();

    Event::assertDispatched(ProcessedUrlsAsAttachments::class, fn (ProcessedUrlsAsAttachments $event): bool => $event->model->is($message));
});
