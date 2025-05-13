<?php

namespace Actengage\Mailbox\Enums;

enum ComparisonOperator: string {
    case Equals = 'eq';
    case NotEquals = 'ne';
    case GreaterThan = 'gt';
    case GreaterThanOrEquals = 'ge';
    case LessThan = 'lt';
    case LessThanOrEquals = 'le';
}