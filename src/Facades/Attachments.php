<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Facades;

use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Actengage\Mailbox\Services\AttachmentService;
use cardinalby\ContentDisposition\ContentDisposition;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Facade;
use Microsoft\Graph\Generated\Models\Attachment;

/**
 * @see AttachmentService
 *
 * @method static Response get(string $url)
 * @method static array<int, string> extractUrls(MailboxMessage $message)
 * @method static bool shouldProcessUrlsAsAttachments(MailboxMessage $message)
 * @method static void processUrlsAsAttachments(MailboxMessage $message)
 * @method static ContentDisposition contentDisposition(Response $response)
 * @method static MailboxMessageAttachment createFromResponse(MailboxMessage $message, Response $response, ContentDisposition|null $disposition = null)
 * @method static MailboxMessageAttachment createFromAttachment(MailboxMessage $message, Attachment $attachment)
 */
class Attachments extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mailbox.attachments';
    }
}
