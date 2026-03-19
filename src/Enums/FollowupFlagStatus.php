<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Enums;

enum FollowupFlagStatus: string
{
    case NotFlagged = 'notFlagged';
    case Flagged = 'flagged';
    case Complete = 'complete';
}
