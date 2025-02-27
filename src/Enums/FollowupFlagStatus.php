<?php

namespace Actengage\Mailbox\Enums;

enum FollowupFlagStatus: string {
    case NotFlagged = 'notFlagged';
    case Flagged = 'flagged';
    case Complete = 'complete';
}