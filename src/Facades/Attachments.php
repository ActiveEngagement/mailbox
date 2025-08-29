<?php

namespace Actengage\Mailbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actengage\Mailbox\Services\AttachmentService
 * @method static \Illuminate\Http\Client\Response get(string $url)
 * @method static array<int,string> extractUrls(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static bool shouldProcessUrlsAsAttachments(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static void processUrlsAsAttachments(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static \cardinalby\ContentDispositioContentDisposition contentDisposition(\Illuminate\Http\Client\Response $response)
 * @method static \Actengage\Mailbox\Models\MailboxMessageAttachment createFromResponse(\Actengage\Mailbox\Models\MailboxMessage $message, \Illuminate\Http\Client\Response $response, \cardinalby\ContentDispositioContentDisposition|null $disposition = null)
 * @method static \Actengage\Mailbox\Models\MailboxMessageAttachment createFromAttachment(\Actengage\Mailbox\Models\MailboxMessage $message, \Microsoft\Graph\Generated\Models\Attachment $attachment)
 */
class Attachments extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox.attachments';
    }
}