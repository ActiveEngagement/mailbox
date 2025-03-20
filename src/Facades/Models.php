<?php

namespace Actengage\Mailbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Actengage\Mailbox\Services\ModelService
 * @method static \Microsoft\Graph\Generated\Models\Message makeMessageModel(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static \Microsoft\Graph\Generated\Models\Message makeDraftMessageModel(\Actengage\Mailbox\Models\MailboxMessage $message)
 * @method static \Microsoft\Graph\Generated\Models\Recipient makeRecipientModel(\Actengage\Mailbox\Data\EmailAddress $email)
 * @method static \Microsoft\Graph\Generated\Models\EmailAddress makeEmailAddressModel(\Actengage\Mailbox\Data\EmailAddress $email)
 * @method static \Microsoft\Graph\Generated\Models\Attachment makeAttachmentModel(\Actengage\Mailbox\Models\MailboxMessageAttachment $attachment)
 * @method static \Microsoft\Graph\Generated\Models\FollowupFlag makeFollowupFlagModel(\Actengage\Mailbox\Data\FollowupFlag $flag)
 * @method static \Microsoft\Graph\Generated\Models\FollowupFlagStatus makeFollowupFlagStatusModel(\Actengage\Mailbox\Enums\FollowupFlagStatus $status)
 * @method static \Microsoft\Graph\Generated\Models\Importance makeImportanceModel(\Actengage\Mailbox\Enums\Importance $importance)
 * @method static \Microsoft\Graph\Generated\Models\DateTimeTimeZone makeDateTimeTimeZoneModel(\DateTime $date)
 */
class Models extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox.models';
    }
}