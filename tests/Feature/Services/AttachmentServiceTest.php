<?php

use Actengage\Mailbox\Jobs\FinishedProcessingUrlsAsAttachments;
use Actengage\Mailbox\Jobs\ProcessUrlAsAttachment;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Services\AttachmentService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

it('should process urls as attachments without pattern', function() {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                    ]
                ]
            ]
        ]
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com'
        ]),
        'from' => 'foo@example.com'
    ]);

    expect(app(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeTrue();
});

it('should process urls as attachments with a pattern', function() {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/(.+)@example.com$/'
                    ]
                ]
            ]
        ]
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com'
        ]),
        'from' => 'foo@example.com'
    ]);

    expect(app(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeTrue();
});

it('should not process urls as attachments without pattern', function() {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => false
                    ]
                ]
            ]
        ]
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com'
        ]),
        'from' => 'foo@example.com'
    ]);

    expect(app(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeFalse();
});

it('should not process urls as attachments with pattern', function() {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/bar@example.com$/'
                    ]
                ]
            ]
        ]
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com'
        ]),
        'from' => 'foo@example.com'
    ]);

    expect(app(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeFalse();
});

it('dispatches the jobs to process urls as attachments', function() {
    Bus::fake();

    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/(.+)@example.com$/'
                    ]
                ]
            ]
        ]
    ]);

    $body = <<< HTML
    <a href="https://google.com">Google.com</a>
    <a href="https://youtube.com">Youtube.com</a>
    HTML;

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com'
        ]),
        'from' => 'foo@example.com',
        'body' => $body
    ]);

    app(AttachmentService::class)->processUrlsAsAttachments($message);

    Bus::assertChained([
        ProcessUrlAsAttachment::class,
        ProcessUrlAsAttachment::class,
        FinishedProcessingUrlsAsAttachments::class
    ]);
});

it('does not dispatch jobs to process urls as attachments', function() {
    Queue::fake();
    Bus::fake();

    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/(.+)@actengage.com$/'
                    ]
                ]
            ]
        ]
    ]);

    $body = <<< HTML
    <a href="https://google.com">Google.com</a>
    <a href="https://youtube.com">Youtube.com</a>
    HTML;

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com'
        ]),
        'from' => 'foo@example.com',
        'body' => $body
    ]);

    app(AttachmentService::class)->processUrlsAsAttachments($message);

    Bus::assertChained([
        FinishedProcessingUrlsAsAttachments::class
    ]);
});