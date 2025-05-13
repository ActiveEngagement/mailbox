<?php

namespace Actengage\Mailbox\Enums;

enum StringFunction: string {
    case StartsWith = 'startswith';
    case EndsWith = 'endswith';
    case Contains = 'contains';
}