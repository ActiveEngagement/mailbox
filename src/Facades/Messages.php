<?php

namespace Actengage\Mailbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actengage\Mailbox\Services\MessageService
 * @method static \Http\Promise\Promise<\Microsoft\Graph\Generated\Models\Message> find(string $userId, string $messageId)
 * @method static \Illuminate\Support\Collection<int,\Microsoft\Graph\Generated\Models\Message> all(string $userId)
 * @method static \Http\Promise\Promise<\Actengage\Mailbox\Models\MailboxMessage> createReply(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static \Http\Promise\Promise<\Microsoft\Graph\Generated\Models\Message> patch(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static \Illuminate\Support\Collection<int, \Microsoft\Graph\Generated\Models\ODataErrors\ODataError> delete(\Actengage\Mailbox\Models\MailboxMessage ...$message)
 * @method static \Http\Promise\Promise<void|null> send(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static \Actengage\Mailbox\Models\MailboxMessage save(\Actengage\Mailbox\Models\Mailbox $mailbox, \Microsoft\Graph\Generated\Models\Message $message)
 */
class Messages extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox.messages';
    }
}