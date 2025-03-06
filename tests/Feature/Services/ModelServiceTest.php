<?php

use Actengage\Mailbox\Data\FollowupFlag;
use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Actengage\Mailbox\Facades\Models;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;

it('makes a Graph API Message model from a MailboxMessage model', function() {
    $attachment = MailboxMessageAttachment::factory()
        ->contents('<html><body>test</body></html>')
        ->count(1);

    $message = MailboxMessage::factory()
        ->has($attachment, 'attachments')
        ->create([
            'reply_to' => ['test@test.com']
        ]);

    $model = Models::makeMessageModel($message);
    
    expect($model->getFrom()->getEmailAddress()->getAddress())->toBe($message->from->email);
    expect($model->getToRecipients())->toHaveCount($message->to->count());
    expect($model->getReplyTo())->toHaveCount($message->reply_to->count());
    expect($model->getCcRecipients())->toHaveCount($message->cc->count());
    expect($model->getBccRecipients())->toHaveCount($message->bcc->count());
    expect($model->getSubject())->toBe($message->subject);
    expect($model->getBody()->getContent())->toBe($message->body);
    expect($model->getBody()->getContentType()->value())->toBe('HTML');
    expect($model->getAttachments())->toHaveCount($message->attachments->count());
});

it('makes a Graph API Recipient model from an EmailAddress instance', function() {
    $message = MailboxMessage::factory()->create();

    $model = Models::makeRecipientModel($message->from);

    expect($model->getEmailAddress()->getAddress())->toBe($message->from->email);
    expect($model->getEmailAddress()->getName())->toBe($message->from->name);

    $message = MailboxMessage::factory()->create([
        'from' => null
    ]);

    $model = Models::makeRecipientModel($message->from);

    expect($model)->toBeNull();
});

it('makes a Graph API EmailAddress model from an EmailAddress instance', function() {
    $message = MailboxMessage::factory()->create();

    $model = Models::makeEmailAddressModel($message->from);

    expect($model->getAddress())->toBe($message->from->email);
    expect($model->getName())->toBe($message->from->name);

    $message = MailboxMessage::factory()->create([
        'from' => null
    ]);

    $model = Models::makeEmailAddressModel($message->from);

    expect($model)->toBeNull();
});

it('makes a Graph API Attachment model from a MailboxMessageAttachment model', function() {
    $attachment = MailboxMessageAttachment::factory()
        ->contents('<html><body>test</body></html>')
        ->count(1);

    $message = MailboxMessage::factory()
        ->has($attachment, 'attachments')
        ->create();

    $attachment = $message->attachments->first();

    $model = Models::makeAttachmentModel($attachment);

    expect($model->getName())->toBe($attachment->name);
    expect($model->getSize())->toBe($attachment->size);
    expect($model->getContentType())->toBe($attachment->content_type);
    expect($model->getBackingStore()->get('contentBytes'))->toBe($attachment->base64_contents);
});

it('it makes a Graph API DateTimeTimeZone model from a DateTime instance', function() {
    $date = now();

    $model = Models::makeDateTimeTimeZoneModel($date);

    expect((string) $model->getDateTime())->toBe((string) $date);
    expect((string) $model->getTimeZone())->toBe((string) $date->getTimezone());
});

it('it makes a Graph API FollupwFlagStatus model from a FollowupFlagStatus enum', function() {
    $status = FollowupFlagStatus::Flagged;

    $model = Models::makeFollowupFlagStatusModel($status);

    expect($model->value())->toBe($status->value);
});

it('makes a Graph API FollowupFlag model from a FollowupImportance instance', function() {
    $flag = FollowupFlag::from([
        'status' => FollowupFlagStatus::Flagged,
        'startDateTime' => now(),
        'dueDateTime' => now(),
    ]);

    $model = Models::makeFollowupFlagModel($flag);

    expect($model->getFlagStatus()->value())->toBe($flag->status->value);
    expect($model->getStartDateTime()->getDateTime())->toBe((string) $flag->startDateTime);
    expect($model->getStartDateTime()->getTimeZone())->toBe((string) $flag->startDateTime->getTimezone());
    expect($model->getDueDateTime()->getDateTime())->toBe((string) $flag->dueDateTime);
    expect($model->getDueDateTime()->getTimeZone())->toBe((string) $flag->dueDateTime->getTimezone());
    expect($model->getCompletedDateTime())->toBeNull();
});