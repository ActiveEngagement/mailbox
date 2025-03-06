<?php

namespace Actengage\Mailbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actengage\Mailbox\Services\MessageService
 * @method static \Microsoft\Graph\Generated\Models\Message find(string $userId, string $messageId)
 * @method static \Illuminate\Support\Collection<int,\Microsoft\Graph\Generated\Models\Message> all(string $userId)
 * @method static \Actengage\Mailbox\Models\MailboxMessage createReply(\Actengage\Mailbox\Models\MailboxMessage)
 * @method static \Actengage\Mailbox\Models\MailboxMessage save(\Actengage\Mailbox\Models\Mailbox $mailbox, \Microsoft\Graph\Generated\Models\Message $message)
 */
class Messages extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox.messages';
    }
}