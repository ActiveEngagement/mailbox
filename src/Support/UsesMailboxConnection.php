<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Support;

trait UsesMailboxConnection
{
    public function getConnectionName(): ?string
    {
        $connection = config('mailbox.database_connection', $this->connection);

        return is_string($connection) ? $connection : null;
    }
}
