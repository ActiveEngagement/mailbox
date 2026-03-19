<?php

use Actengage\Mailbox\Data\FollowupFlag;
use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Carbon\Carbon;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\FollowupFlag as BaseFollowupFlag;
use Microsoft\Graph\Generated\Models\FollowupFlagStatus as GraphFollowupFlagStatus;

it('creates from a Graph API FollowupFlag with all dates', function (): void {
    $startDate = new DateTimeTimeZone;
    $startDate->setDateTime('2025-01-01T00:00:00Z');
    $startDate->setTimeZone('UTC');

    $dueDate = new DateTimeTimeZone;
    $dueDate->setDateTime('2025-01-15T00:00:00Z');
    $dueDate->setTimeZone('UTC');

    $completedDate = new DateTimeTimeZone;
    $completedDate->setDateTime('2025-01-10T00:00:00Z');
    $completedDate->setTimeZone('UTC');

    $base = new BaseFollowupFlag;
    $base->setFlagStatus(new GraphFollowupFlagStatus(GraphFollowupFlagStatus::COMPLETE));
    $base->setStartDateTime($startDate);
    $base->setDueDateTime($dueDate);
    $base->setCompletedDateTime($completedDate);

    $flag = FollowupFlag::fromFollowupFlag($base);

    expect($flag->status)->toBe(FollowupFlagStatus::Complete);
    expect($flag->startDateTime)->toBeInstanceOf(Carbon::class);
    expect($flag->dueDateTime)->toBeInstanceOf(Carbon::class);
    expect($flag->completedDateTime)->toBeInstanceOf(Carbon::class);
});

it('creates from a Graph API FollowupFlag without dates', function (): void {
    $base = new BaseFollowupFlag;
    $base->setFlagStatus(new GraphFollowupFlagStatus(GraphFollowupFlagStatus::NOT_FLAGGED));

    $flag = FollowupFlag::fromFollowupFlag($base);

    expect($flag->status)->toBe(FollowupFlagStatus::NotFlagged);
    expect($flag->startDateTime)->toBeNull();
    expect($flag->dueDateTime)->toBeNull();
    expect($flag->completedDateTime)->toBeNull();
});

it('defaults to NotFlagged when flagStatus is null', function (): void {
    $base = new BaseFollowupFlag;

    $flag = FollowupFlag::fromFollowupFlag($base);

    expect($flag->status)->toBe(FollowupFlagStatus::NotFlagged);
});
