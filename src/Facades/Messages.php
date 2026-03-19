<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Facades;

use Actengage\Mailbox\Data\Conditional;
use Actengage\Mailbox\Data\Filter;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Services\MessageService;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;

/**
 * @see MessageService
 *
 * @method static Promise<Message|null> find(string $userId, string $messageId)
 * @method static void all(string $userId, callable $iterator, Conditional|Filter|string|null $filter = null)
 * @method static Promise<MailboxMessage|null> create(Mailbox $mailbox)
 * @method static Promise<MailboxMessage|null> createReply(MailboxMessage $message)
 * @method static Promise<MailboxMessage|null> createReplyAll(MailboxMessage $message)
 * @method static Promise<MailboxMessage|null> createForward(MailboxMessage $message)
 * @method static Promise<Message|null> patch(MailboxMessage $message)
 * @method static Promise<Message|null> move(MailboxMessage $message, MailboxFolder $folder)
 * @method static Collection<int, ODataError> delete(MailboxMessage ...$message)
 * @method static Promise<void|null> send(MailboxMessage $message)
 * @method static MailboxMessage save(Mailbox $mailbox, Message $message)
 */
class Messages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mailbox.messages';
    }
}
