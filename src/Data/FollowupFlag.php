<?php

namespace Actengage\Mailbox\Data;

use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Carbon\Carbon;
use DateTime;
use Microsoft\Graph\Generated\Models\FollowupFlag as BaseFollowupFlag;
use Spatie\LaravelData\Data;

/** @typescript */
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
     *
     * @param BaseFollowupFlag $value
     * @return static
     */
    public static function fromFollowupFlag(BaseFollowupFlag $value): static
    {
        $dueDateTime = $value->getDueDateTime()
            ? new Carbon($value->getDueDateTime()->getDateTime(), $value->getDueDateTime()->getTimeZone())
            : null;

        $startDateTime = $value->getStartDateTime()
            ? new Carbon($value->getStartDateTime()->getDateTime(), $value->getStartDateTime()->getTimeZone())
            : null;

        $completedDateTime = $value->getCompletedDateTime()
            ? new Carbon($value->getCompletedDateTime()->getDateTime(), $value->getCompletedDateTime()->getTimeZone())
            : null;

        return static::from([
            'status' => FollowupFlagStatus::from($value->getFlagStatus()->value()),
            'dueDateTime' => $dueDateTime,
            'startDateTime' => $startDateTime,
            'completedDateTime' => $completedDateTime,
        ]);
    }
}