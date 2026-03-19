<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Facades;

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Services\FolderService;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Microsoft\Graph\Generated\Models\MailFolder;

/**
 * @see FolderService
 *
 * @method static Collection<int, MailFolder> all(string $userId)
 * @method static Collection<int, MailFolder> tree(string $userId)
 * @method static Promise<MailFolder|null> find(Mailbox|string $mailbox, string $folderId)
 * @method static MailboxFolder save(Mailbox $mailbox, MailFolder $folder)
 */
class Folders extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mailbox.folders';
    }
}
