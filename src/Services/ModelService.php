<?php

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
     *
     * @param MailboxMessage $message
     * @return Message
     */
    public function makeMessageModel(MailboxMessage $message): Message
    {
        $type = new BodyType('HTML');

        $body = new ItemBody();
        $body->setContent($message->body);
        $body->setContentType($type);

        $model = new Message();
        $model->setFrom($this->makeRecipientModel($message->from));
        $model->setSubject($message->subject);
        $model->setBody($body);
        $model->setFlag($this->makeFollowupFlagModel($message->flag));
        $model->setImportance($this->makeImportanceModel($message->importance));
        $model->setIsDraft($message->is_draft);
        
        if($message->to) {
            $model->setToRecipients(collect($message->to)->map(function(EmailAddress $value) {
                return $this->makeRecipientModel($value);
            })->all());
        }

        if($message->reply_to) {
            $model->setReplyTo(collect($message->reply_to)->map(function(EmailAddress $value) {
                return $this->makeRecipientModel($value);
            })->all());
        }

        if($message->cc) {
            $model->setCcRecipients(collect($message->cc)->map(function(EmailAddress $value) {
                return $this->makeRecipientModel($value);
            })->all());
        }

        if($message->bcc) {
            $model->setBccRecipients(collect($message->bcc)->map(function(EmailAddress $value) {
                return $this->makeRecipientModel($value);
            })->all());
        }

        if($message->attachments) {
            $model->setAttachments(collect($message->attachments)->map(function(MailboxMessageAttachment $attachment) {
                return $this->makeAttachmentModel($attachment);
            })->all());
        }  

        return $model;
    }

    /**
     * Make a Graph API Recipient model from the given value.
     *
     * @param EmailAddress|null $email
     * @return Recipient|null
     */
    public function makeRecipientModel(?EmailAddress $email): ?Recipient
    {
        if(!$email) {
            return null;
        }

        $model = new Recipient();
        $model->setEmailAddress($this->makeEmailAddressModel($email));

        return $model;
    }

    /**
     * Make a Graph API EmailAddress model from the given value.
     *
     * @param EmailAddress|null $email
     * @return Recipient|null
     */
    public function makeEmailAddressModel(?EmailAddress $email): ?EmailAddressModel
    {
        if(!$email) {
            return null;
        }

        $model = new EmailAddressModel();
        $model->setAddress($email->email);
        $model->setName($email->name);

        return $model;
    }

    /**
     * Make a Graph API Attachment model from the given value.
     *
     * @param MailboxMessageAttachment $attachment
     * @return Attachment|null
     */
    public function makeAttachmentModel(?MailboxMessageAttachment $attachment): ?Attachment
    {
        if(!$attachment) {
            return null;
        }

        $model = new Attachment();
        $model->setName($attachment->name);
        $model->setContentType($attachment->content_type);
        $model->setSize($attachment->size);
        $model->setLastModifiedDateTime($attachment->last_modified_at);
        $model->getBackingStore()->set('contentBytes', $attachment->base64_contents);

        return $model;
    }

    /**
     * Make a Graph API FollowupFlag model from the given value.
     *
     * @param FollowupFlag $flag
     * @return FollowupFlagModel|null
     */
    public function makeFollowupFlagModel(?FollowupFlag $flag): ?FollowupFlagModel
    {
        if(!$flag) {
            return null;
        }
        
        $model = new FollowupFlagModel();
        $model->setCompletedDateTime($this->makeDateTimeTimeZoneModel($flag->completedDateTime));
        $model->setDueDateTime($this->makeDateTimeTimeZoneModel($flag->dueDateTime));
        $model->setStartDateTime($this->makeDateTimeTimeZoneModel($flag->startDateTime));
        $model->setFlagStatus($this->makeFollowupFlagStatusModel($flag->status));

        return $model;
    }

    /**
     * Make a Graph API FollowupFlagStatus model from the given value.
     *
     * @param FollowupFlagStatus $status
     * @return FollowupFlagStatusModel|null
     */
    public function makeFollowupFlagStatusModel(?FollowupFlagStatus $status): ?FollowupFlagStatusModel
    {
        if(!$status) {
            return null;
        }

        return new FollowupFlagStatusModel($status->value);
    }

    /**
     * Make a Graph API Importance model from the given value.
     *
     * @param Importance $importance
     * @return ImportanceModel|null
     */
    public function makeImportanceModel(?Importance $importance): ?ImportanceModel
    {
        if(!$importance) {
            return null;
        }

        return new ImportanceModel($importance->value);
    }

    /**
     * Make a Graph API DateTimeTimeZone model from the given value.
     *
     * @param DateTime $importance
     * @return DateTimeTimeZone|null
     */
    public function makeDateTimeTimeZoneModel(?DateTime $date): ?DateTimeTimeZone
    {
        if(!$date) {
            return null;
        }

        $model = new DateTimeTimeZone();
        $model->setDateTime((string) $date);
        $model->setTimeZone((string) $date->getTimezone());

        return $model;
    }
}