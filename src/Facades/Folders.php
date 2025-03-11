<?php

namespace Actengage\Mailbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actengage\Mailbox\Services\GraphServiceClient
 * @method static \Illuminate\Support\Collection<int,\Microsoft\Graph\Generated\Models\MailFolder> all(string $userId)
 * @method static \Illuminate\Support\Collection<int,\Microsoft\Graph\Generated\Models\MailFolder> tree(string $userId)
 * @method static \Http\Promise\Promise<\Microsoft\Graph\Generated\Models\MailFolder> find(string $userId, string $folderId)
 * @method static \Actengage\Mailbox\Models\MailboxFolder save(\Actengage\Mailbox\Models\Mailbox $mailbox, \Microsoft\Graph\Generated\Models\MailFolder $folder)
 */
class Folders extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox.folders';
    }
}