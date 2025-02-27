<?php

namespace Actengage\Mailbox\Enums;

enum Importance: string {
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
}