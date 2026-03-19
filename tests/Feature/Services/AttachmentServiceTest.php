<?php

use Actengage\Mailbox\Jobs\FinishedProcessingUrlsAsAttachments;
use Actengage\Mailbox\Jobs\ProcessUrlAsAttachment;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Actengage\Mailbox\Services\AttachmentService;
use Actengage\Mailbox\Services\ClientService;
use cardinalby\ContentDisposition\ContentDisposition;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Microsoft\Graph\Generated\Models\Attachment;

it('should process urls as attachments without pattern', function (): void {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com',
        ]),
        'from' => 'foo@example.com',
    ]);

    expect(resolve(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeTrue();
});

it('should process urls as attachments with a pattern', function (): void {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/(.+)@example.com$/',
                    ],
                ],
            ],
        ],
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com',
        ]),
        'from' => 'foo@example.com',
    ]);

    expect(resolve(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeTrue();
});

it('should not process urls as attachments without pattern', function (): void {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => false,
                    ],
                ],
            ],
        ],
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com',
        ]),
        'from' => 'foo@example.com',
    ]);

    expect(resolve(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeFalse();
});

it('should not process urls as attachments with pattern', function (): void {
    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/bar@example.com$/',
                    ],
                ],
            ],
        ],
    ]);

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com',
        ]),
        'from' => 'foo@example.com',
    ]);

    expect(resolve(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeFalse();
});

it('dispatches the jobs to process urls as attachments', function (): void {
    Bus::fake();

    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/(.+)@example.com$/',
                    ],
                ],
            ],
        ],
    ]);

    $body = <<< 'HTML'
    <a href="https://google.com">Google.com</a>
    <a href="https://youtube.com">Youtube.com</a>
    HTML;

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com',
        ]),
        'from' => 'foo@example.com',
        'body' => $body,
    ]);

    resolve(AttachmentService::class)->processUrlsAsAttachments($message);

    Bus::assertChained([
        ProcessUrlAsAttachment::class,
        ProcessUrlAsAttachment::class,
        FinishedProcessingUrlsAsAttachments::class,
    ]);
});

it('does not dispatch jobs to process urls as attachments', function (): void {
    Queue::fake();
    Bus::fake();

    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                        'pattern' => '/(.+)@actengage.com$/',
                    ],
                ],
            ],
        ],
    ]);

    $body = <<< 'HTML'
    <a href="https://google.com">Google.com</a>
    <a href="https://youtube.com">Youtube.com</a>
    HTML;

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com',
        ]),
        'from' => 'foo@example.com',
        'body' => $body,
    ]);

    resolve(AttachmentService::class)->processUrlsAsAttachments($message);

    Bus::assertChained([
        FinishedProcessingUrlsAsAttachments::class,
    ]);
});

it('performs a GET request for a URL', function (): void {
    Http::fake([
        'https://example.com/file.pdf' => Http::response('content', 200),
    ]);

    $service = resolve(AttachmentService::class);
    $response = $service->get('https://example.com/file.pdf');

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->status())->toBe(200);
});

it('extracts URLs from message body', function (): void {
    $body = '<html><body><a href="https://example.com/file.pdf">File</a><a href="https://example.com/page">Page</a><a href="mailto:test@test.com">Email</a><a href="">Empty</a><a>No href</a></body></html>';

    $message = MailboxMessage::factory()->create([
        'body' => $body,
    ]);

    $service = resolve(AttachmentService::class);
    $urls = $service->extractUrls($message);

    expect($urls)->toHaveCount(2);
    expect($urls[0])->toBe('https://example.com/file.pdf');
    expect($urls[1])->toBe('https://example.com/page');
});

it('returns empty array when no URLs in body', function (): void {
    $message = MailboxMessage::factory()->create([
        'body' => '<html><body><p>No links here</p></body></html>',
    ]);

    $service = resolve(AttachmentService::class);
    $urls = $service->extractUrls($message);

    expect($urls)->toBe([]);
});

it('parses content disposition from response', function (): void {
    Http::fake([
        'https://example.com/file.pdf' => Http::response('content', 200, [
            'content-disposition' => 'attachment; filename="test.pdf"',
        ]),
    ]);

    $response = Http::get('https://example.com/file.pdf');

    $service = resolve(AttachmentService::class);
    $disposition = $service->contentDisposition($response);

    expect($disposition)->toBeInstanceOf(ContentDisposition::class);
    expect($disposition->getFilename())->toBe('test.pdf');
});

it('creates an attachment from HTTP response', function (): void {
    Storage::fake('local');

    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create([
        'mailbox_id' => $mailbox->id,
    ]);

    Http::fake([
        'https://example.com/file.pdf' => Http::response('file content here', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="test.pdf"',
        ]),
    ]);

    $response = Http::get('https://example.com/file.pdf');
    $disposition = ContentDisposition::parse('attachment; filename="test.pdf"');

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('config')->with('storage_disk', 'local')->andReturn('local');
    $clientService->shouldReceive('config')->with('storage_visibility', 'private')->andReturn('private');

    $service = new AttachmentService($clientService);
    $model = $service->createFromResponse($message, $response, $disposition);

    expect($model)->toBeInstanceOf(MailboxMessageAttachment::class);
    expect($model->name)->toBe('test.pdf');
    expect($model->disk)->toBe('local');
    expect($model->mailbox_id)->toBe($mailbox->id);
    expect($model->exists)->toBeTrue();

    Storage::disk('local')->assertExists($model->path);
});

it('returns existing attachment if name already exists for message', function (): void {
    Storage::fake('local');

    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create([
        'mailbox_id' => $mailbox->id,
    ]);

    $existing = MailboxMessageAttachment::factory()->create([
        'message_id' => $message->id,
        'mailbox_id' => $mailbox->id,
        'name' => 'test.pdf',
    ]);

    Http::fake([
        'https://example.com/file.pdf' => Http::response('content', 200, [
            'Content-Disposition' => 'attachment; filename="test.pdf"',
        ]),
    ]);

    $response = Http::get('https://example.com/file.pdf');
    $disposition = ContentDisposition::parse('attachment; filename="test.pdf"');

    $clientService = Mockery::mock(ClientService::class);
    $service = new AttachmentService($clientService);
    $model = $service->createFromResponse($message, $response, $disposition);

    expect($model->id)->toBe($existing->id);
});

it('creates an attachment from Graph API Attachment model', function (): void {
    Storage::fake('local');

    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create([
        'mailbox_id' => $mailbox->id,
    ]);

    $attachment = new Attachment;
    $attachment->setName('graph-file.pdf');
    $attachment->setContentType('application/pdf');
    $attachment->setSize(1024);
    $attachment->setLastModifiedDateTime(new DateTime('2025-01-01T00:00:00Z'));
    $attachment->getBackingStore()->set('contentBytes', base64_encode('file content'));

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('config')->with('storage_disk', 'local')->andReturn('local');
    $clientService->shouldReceive('config')->with('storage_visibility', 'private')->andReturn('private');

    $service = new AttachmentService($clientService);
    $model = $service->createFromAttachment($message, $attachment);

    expect($model)->toBeInstanceOf(MailboxMessageAttachment::class);
    expect($model->name)->toBe('graph-file.pdf');
    expect($model->content_type)->toBe('application/pdf');
    expect($model->size)->toBe(1024);
    expect($model->exists)->toBeTrue();

    Storage::disk('local')->assertExists($model->path);
});

it('returns existing attachment when creating from Graph API Attachment', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create([
        'mailbox_id' => $mailbox->id,
    ]);

    $existing = MailboxMessageAttachment::factory()->create([
        'message_id' => $message->id,
        'mailbox_id' => $mailbox->id,
        'name' => 'graph-file.pdf',
    ]);

    $attachment = new Attachment;
    $attachment->setName('graph-file.pdf');

    $clientService = Mockery::mock(ClientService::class);
    $service = new AttachmentService($clientService);
    $model = $service->createFromAttachment($message, $attachment);

    expect($model->id)->toBe($existing->id);
});

it('returns false when from is null for shouldProcessUrlsAsAttachments', function (): void {
    $message = MailboxMessage::factory()->create([
        'from' => null,
    ]);

    expect(resolve(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeFalse();
});

it('returns false when mailbox config is missing', function (): void {
    config()->set('mailbox.mailboxes', []);

    $message = MailboxMessage::factory()->create([
        'from' => 'test@example.com',
    ]);

    expect(resolve(AttachmentService::class)->shouldProcessUrlsAsAttachments($message))->toBeFalse();
});

it('dispatches FinishedProcessingUrlsAsAttachments when chain fails', function (): void {
    Queue::fake();

    config()->set([
        'mailbox' => [
            ...config('mailbox'),
            'mailboxes' => [
                'test@test.com' => [
                    'process_urls_as_attachments' => [
                        'enabled' => true,
                    ],
                ],
            ],
        ],
    ]);

    $body = '<a href="https://example.com/file.pdf">File</a>';

    $message = MailboxMessage::factory()->create([
        'mailbox_id' => Mailbox::factory()->create([
            'email' => 'test@test.com',
        ]),
        'from' => 'foo@example.com',
        'body' => $body,
    ]);

    $catchCallback = null;

    Bus::shouldReceive('chain')
        ->once()
        ->andReturnUsing(function () use (&$catchCallback): object {
            return new class($catchCallback)
            {
                private mixed $cb;

                public function __construct(mixed &$ref)
                {
                    $this->cb = &$ref;
                }

                public function catch(Closure $callback): static
                {
                    $this->cb = $callback;

                    return $this;
                }

                public function dispatch(): void
                {
                    // Simulate chain failure by invoking the catch callback
                    ($this->cb)();
                }
            };
        });

    Bus::shouldReceive('dispatch')->once();

    resolve(AttachmentService::class)->processUrlsAsAttachments($message);
});
