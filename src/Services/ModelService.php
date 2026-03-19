<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Data\EmailAddress;
use Actengage\Mailbox\Data\FollowupFlag;
use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Actengage\Mailbox\Enums\Importance;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use DateTime;
use Microsoft\Graph\Generated\Models\Attachment;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\EmailAddress as EmailAddressModel;
use Microsoft\Graph\Generated\Models\FollowupFlag as FollowupFlagModel;
use Microsoft\Graph\Generated\Models\FollowupFlagStatus as FollowupFlagStatusModel;
use Microsoft\Graph\Generated\Models\Importance as ImportanceModel;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\Recipient;

class ModelService
{
    /**
     * Instantiate a Graph API Message model from the given value.
     */
    public function makeMessageModel(MailboxMessage $message): Message
    {
        if ($message->is_draft) {
            return $this->makeDraftMessageModel($message);
        }

        $model = new Message;
        $model->setId($message->external_id);
        $model->setFlag($this->makeFollowupFlagModel($message->flag));
        $model->setImportance($this->makeImportanceModel($message->importance));
        $model->setIsRead($message->is_read);

        return $model;
    }

    public function makeDraftMessageModel(MailboxMessage $message): Message
    {
        $type = new BodyType('HTML');

        $body = new ItemBody;
        $body->setContent($message->body);
        $body->setContentType($type);

        $model = new Message;
        $model->setId($message->external_id);
        $model->setFrom($this->makeRecipientModel($message->from));
        $model->setSubject($message->subject);
        $model->setBody($body);
        $model->setFlag($this->makeFollowupFlagModel($message->flag));
        $model->setImportance($this->makeImportanceModel($message->importance));
        $model->setIsDraft($message->is_draft);
        $model->setIsRead($message->is_read);

        if ($message->to) {
            $model->setToRecipients(collect($message->to)->map(fn (EmailAddress $value): ?Recipient => $this->makeRecipientModel($value))->filter()->values()->all());
        }

        if ($message->reply_to) {
            $model->setReplyTo(collect($message->reply_to)->map(fn (EmailAddress $value): ?Recipient => $this->makeRecipientModel($value))->filter()->values()->all());
        }

        if ($message->cc) {
            $model->setCcRecipients(collect($message->cc)->map(fn (EmailAddress $value): ?Recipient => $this->makeRecipientModel($value))->filter()->values()->all());
        }

        if ($message->bcc) {
            $model->setBccRecipients(collect($message->bcc)->map(fn (EmailAddress $value): ?Recipient => $this->makeRecipientModel($value))->filter()->values()->all());
        }

        $model->setAttachments(collect($message->attachments)->map(fn (MailboxMessageAttachment $attachment): ?Attachment => $this->makeAttachmentModel($attachment))->filter()->values()->all());

        return $model;
    }

    /**
     * Make a Graph API Recipient model from the given value.
     */
    public function makeRecipientModel(?EmailAddress $email): ?Recipient
    {
        if (! $email instanceof EmailAddress) {
            return null;
        }

        $model = new Recipient;
        $model->setEmailAddress($this->makeEmailAddressModel($email));

        return $model;
    }

    /**
     * Make a Graph API EmailAddress model from the given value.
     */
    public function makeEmailAddressModel(?EmailAddress $email): ?EmailAddressModel
    {
        if (! $email instanceof EmailAddress) {
            return null;
        }

        $model = new EmailAddressModel;
        $model->setAddress($email->email);
        $model->setName($email->name);

        return $model;
    }

    /**
     * Make a Graph API Attachment model from the given value.
     */
    public function makeAttachmentModel(?MailboxMessageAttachment $attachment): ?Attachment
    {
        if (! $attachment instanceof MailboxMessageAttachment) {
            return null;
        }

        $model = new Attachment;
        $model->setName($attachment->name);
        $model->setContentType($attachment->content_type);
        $model->setSize($attachment->size);
        $model->setLastModifiedDateTime($attachment->last_modified_at);
        $model->getBackingStore()->set('contentBytes', $attachment->base64_contents);

        return $model;
    }

    /**
     * Make a Graph API FollowupFlag model from the given value.
     */
    public function makeFollowupFlagModel(?FollowupFlag $flag): ?FollowupFlagModel
    {
        if (! $flag instanceof FollowupFlag) {
            return null;
        }

        $model = new FollowupFlagModel;
        $model->setCompletedDateTime($this->makeDateTimeTimeZoneModel($flag->completedDateTime));
        $model->setDueDateTime($this->makeDateTimeTimeZoneModel($flag->dueDateTime));
        $model->setStartDateTime($this->makeDateTimeTimeZoneModel($flag->startDateTime));
        $model->setFlagStatus($this->makeFollowupFlagStatusModel($flag->status));

        return $model;
    }

    /**
     * Make a Graph API FollowupFlagStatus model from the given value.
     */
    public function makeFollowupFlagStatusModel(?FollowupFlagStatus $status): ?FollowupFlagStatusModel
    {
        if (! $status instanceof FollowupFlagStatus) {
            return null;
        }

        return new FollowupFlagStatusModel($status->value);
    }

    /**
     * Make a Graph API Importance model from the given value.
     */
    public function makeImportanceModel(?Importance $importance): ?ImportanceModel
    {
        if (! $importance instanceof Importance) {
            return null;
        }

        return new ImportanceModel($importance->value);
    }

    /**
     * Make a Graph API DateTimeTimeZone model from the given value.
     */
    public function makeDateTimeTimeZoneModel(?DateTime $date): ?DateTimeTimeZone
    {
        if (! $date instanceof DateTime) {
            return null;
        }

        $model = new DateTimeTimeZone;
        $model->setDateTime($date->format('Y-m-d\TH:i:s\Z'));
        $model->setTimeZone($date->getTimezone()->getName());

        return $model;
    }
}
