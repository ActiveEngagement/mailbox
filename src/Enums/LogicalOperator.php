<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Enums;

enum LogicalOperator: string
{
    case And = 'and';
    case Or = 'or';
}
