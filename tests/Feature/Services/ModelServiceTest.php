<?php

use Actengage\Mailbox\Data\FollowupFlag;
use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Actengage\Mailbox\Facades\Models;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;

it('makes a Graph API Message model from a MailboxMessage model', function (): void {
    $attachment = MailboxMessageAttachment::factory()
        ->contents('<html><body>test</body></html>')
        ->count(1);

    $message = MailboxMessage::factory()
        ->has($attachment, 'attachments')
        ->create([
            'from' => 'test@test.com',
            'reply_to' => ['test@test.com'],
        ]);

    $model = Models::makeDraftMessageModel($message);

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

it('makes a Graph API Recipient model from an EmailAddress instance', function (): void {
    $message = MailboxMessage::factory()->create();

    $model = Models::makeRecipientModel($message->from);

    expect($model->getEmailAddress()->getAddress())->toBe($message->from->email);
    expect($model->getEmailAddress()->getName())->toBe($message->from->name);

    $message = MailboxMessage::factory()->create([
        'from' => null,
    ]);

    $model = Models::makeRecipientModel($message->from);

    expect($model)->toBeNull();
});

it('makes a Graph API EmailAddress model from an EmailAddress instance', function (): void {
    $message = MailboxMessage::factory()->create();

    $model = Models::makeEmailAddressModel($message->from);

    expect($model->getAddress())->toBe($message->from->email);
    expect($model->getName())->toBe($message->from->name);

    $message = MailboxMessage::factory()->create([
        'from' => null,
    ]);

    $model = Models::makeEmailAddressModel($message->from);

    expect($model)->toBeNull();
});

it('makes a Graph API Attachment model from a MailboxMessageAttachment model', function (): void {
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

it('it makes a Graph API DateTimeTimeZone model from a DateTime instance', function (): void {
    $date = now();

    $model = Models::makeDateTimeTimeZoneModel($date);

    expect((string) $model->getDateTime())->toBe($date->format('Y-m-d\TH:i:s\Z'));
    expect((string) $model->getTimeZone())->toBe($date->getTimezone()->getName());
});

it('it makes a Graph API FollupwFlagStatus model from a FollowupFlagStatus enum', function (): void {
    $status = FollowupFlagStatus::Flagged;

    $model = Models::makeFollowupFlagStatusModel($status);

    expect($model->value())->toBe($status->value);
});

it('makes a Graph API FollowupFlag model from a FollowupImportance instance', function (): void {
    $flag = FollowupFlag::from([
        'status' => FollowupFlagStatus::Flagged,
        'startDateTime' => now(),
        'dueDateTime' => now(),
    ]);

    $model = Models::makeFollowupFlagModel($flag);

    expect($model->getFlagStatus()->value())->toBe($flag->status->value);
    expect($model->getStartDateTime()->getDateTime())->toBe($flag->startDateTime->format('Y-m-d\TH:i:s\Z'));
    expect($model->getStartDateTime()->getTimeZone())->toBe($flag->startDateTime->getTimezone()->getName());
    expect($model->getDueDateTime()->getDateTime())->toBe($flag->dueDateTime->format('Y-m-d\TH:i:s\Z'));
    expect($model->getDueDateTime()->getTimeZone())->toBe($flag->dueDateTime->getTimezone()->getName());
    expect($model->getCompletedDateTime())->toBeNull();
});

it('makes a non-draft Message model', function (): void {
    $message = MailboxMessage::factory()->create([
        'is_draft' => false,
        'is_read' => true,
    ]);

    $model = Models::makeMessageModel($message);

    expect($model->getId())->toBe($message->external_id);
    expect($model->getIsRead())->toBeTrue();
    expect($model->getIsDraft())->toBeNull();
    expect($model->getSubject())->toBeNull();
});

it('makes a draft Message model via makeMessageModel', function (): void {
    $message = MailboxMessage::factory()->create([
        'is_draft' => true,
        'is_read' => false,
        'from' => 'test@test.com',
        'subject' => 'Draft Subject',
    ]);

    $model = Models::makeMessageModel($message);

    expect($model->getId())->toBe($message->external_id);
    expect($model->getIsDraft())->toBeTrue();
    expect($model->getSubject())->toBe($message->subject);
    expect($model->getFrom()->getEmailAddress()->getAddress())->toBe($message->from->email);
});

it('returns null for null attachment model', function (): void {
    expect(Models::makeAttachmentModel(null))->toBeNull();
});

it('returns null for null followup flag model', function (): void {
    expect(Models::makeFollowupFlagModel(null))->toBeNull();
});

it('returns null for null followup flag status model', function (): void {
    expect(Models::makeFollowupFlagStatusModel(null))->toBeNull();
});

it('returns null for null importance model', function (): void {
    expect(Models::makeImportanceModel(null))->toBeNull();
});

it('returns null for null datetime timezone model', function (): void {
    expect(Models::makeDateTimeTimeZoneModel(null))->toBeNull();
});
