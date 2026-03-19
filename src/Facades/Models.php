<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Facades;

use Actengage\Mailbox\Data\EmailAddress;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Actengage\Mailbox\Services\ModelService;
use DateTime;
use Illuminate\Support\Facades\Facade;
use Microsoft\Graph\Generated\Models\Attachment;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\FollowupFlag;
use Microsoft\Graph\Generated\Models\FollowupFlagStatus;
use Microsoft\Graph\Generated\Models\Importance;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\Recipient;

/**
 * @see ModelService
 *
 * @method static Message makeMessageModel(MailboxMessage $message)
 * @method static Message makeDraftMessageModel(MailboxMessage $message)
 * @method static Recipient|null makeRecipientModel(?EmailAddress $email)
 * @method static \Microsoft\Graph\Generated\Models\EmailAddress|null makeEmailAddressModel(?EmailAddress $email)
 * @method static Attachment|null makeAttachmentModel(?MailboxMessageAttachment $attachment)
 * @method static FollowupFlag|null makeFollowupFlagModel(?\Actengage\Mailbox\Data\FollowupFlag $flag)
 * @method static FollowupFlagStatus|null makeFollowupFlagStatusModel(?\Actengage\Mailbox\Enums\FollowupFlagStatus $status)
 * @method static Importance|null makeImportanceModel(?\Actengage\Mailbox\Enums\Importance $importance)
 * @method static DateTimeTimeZone|null makeDateTimeTimeZoneModel(?DateTime $date)
 */
class Models extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mailbox.models';
    }
}
