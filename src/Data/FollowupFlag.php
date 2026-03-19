<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Data;

use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Carbon\Carbon;
use DateTime;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\FollowupFlag as BaseFollowupFlag;
use Spatie\LaravelData\Data;

/** @typescript FollowupFlag */
class FollowupFlag extends Data
{
    public function __construct(
        public FollowupFlagStatus $status,
        public ?DateTime $startDateTime = null,
        public ?DateTime $dueDateTime = null,
        public ?DateTime $completedDateTime = null,
    ) {
        //
    }

    /**
     * Create from
     */
    public static function fromFollowupFlag(BaseFollowupFlag $value): static
    {
        $dueDateTime = $value->getDueDateTime() instanceof DateTimeTimeZone
            ? new Carbon($value->getDueDateTime()->getDateTime(), $value->getDueDateTime()->getTimeZone())
            : null;

        $startDateTime = $value->getStartDateTime() instanceof DateTimeTimeZone
            ? new Carbon($value->getStartDateTime()->getDateTime(), $value->getStartDateTime()->getTimeZone())
            : null;

        $completedDateTime = $value->getCompletedDateTime() instanceof DateTimeTimeZone
            ? new Carbon($value->getCompletedDateTime()->getDateTime(), $value->getCompletedDateTime()->getTimeZone())
            : null;

        $flagStatus = $value->getFlagStatus();

        return static::from([
            'status' => $flagStatus instanceof \Microsoft\Graph\Generated\Models\FollowupFlagStatus
                ? FollowupFlagStatus::from($flagStatus->value())
                : FollowupFlagStatus::NotFlagged,
            'dueDateTime' => $dueDateTime,
            'startDateTime' => $startDateTime,
            'completedDateTime' => $completedDateTime,
        ]);
    }
}
