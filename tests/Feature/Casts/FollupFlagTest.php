<?php

use Actengage\Mailbox\Data\FollowupFlag;
use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Actengage\Mailbox\Models\MailboxMessage;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\FollowupFlag as BaseFollowupFlag;
use Microsoft\Graph\Generated\Models\FollowupFlagStatus as BaseFollowupFlagStatus;

function createFlag(): BaseFollowupFlag {
    $flagStatus = new BaseFollowupFlagStatus(BaseFollowupFlagStatus::FLAGGED);

    $flagStartDate = new DateTimeTimeZone();
    $flagStartDate->setDateTime((string) now());
    $flagStartDate->setTimeZone('UTC');

    $flagDueDate = new DateTimeTimeZone();
    $flagDueDate->setDateTime((string) now()->addDay(1));
    $flagDueDate->setTimeZone('UTC');

    $flag = new BaseFollowupFlag();
    $flag->setFlagStatus($flagStatus);
    $flag->setStartDateTime($flagStartDate);
    $flag->setDueDateTime($flagDueDate);

    return $flag;
}

function createCompletedFlag(): BaseFollowupFlag {
    $flagStatus = new BaseFollowupFlagStatus(BaseFollowupFlagStatus::COMPLETE);

    $flagCompletedDate = new DateTimeTimeZone();
    $flagCompletedDate->setDateTime((string) now());
    $flagCompletedDate->setTimeZone('UTC');

    $flag = new BaseFollowupFlag();
    $flag->setFlagStatus($flagStatus);
    $flag->setCompletedDateTime($flagCompletedDate);

    return $flag;
}

it('casts from accepted types correctly', function() {
    $message = MailboxMessage::factory()->make();

    expect($message->flag)->toBeInstanceOf(FollowupFlag::class);

    $message->flag = FollowupFlag::from([
        'status' => FollowupFlagStatus::NotFlagged
    ]);

    expect($message->flag->status)->toBe(FollowupFlagStatus::NotFlagged);
    expect($message->flag->startDateTime)->toBeNull();
    expect($message->flag->dueDateTime)->toBeNull();
    expect($message->flag->completedDateTime)->toBeNull();

    /** @var FollowupFlag */
    $message->flag = null;

    expect($message->flag->status)->toBe(FollowupFlagStatus::NotFlagged);
    expect($message->flag->startDateTime)->toBeNull();
    expect($message->flag->dueDateTime)->toBeNull();
    expect($message->flag->completedDateTime)->toBeNull();

    /** @var FollowupFlag */
    $message->flag = createFlag();

    expect($message->flag->status)->toBe(FollowupFlagStatus::Flagged);
    expect($message->flag->startDateTime)->not->toBeNull();
    expect($message->flag->dueDateTime)->not->toBeNull();
    expect($message->flag->completedDateTime)->toBeNull();

    /** @var FollowupFlag */
    $message->flag = createCompletedFlag();

    expect($message->flag->status)->toBe(FollowupFlagStatus::Complete);
    expect($message->flag->startDateTime)->toBeNull();
    expect($message->flag->dueDateTime)->toBeNull();
    expect($message->flag->completedDateTime)->not->toBeNull();
});