<?php

use Actengage\Mailbox\Facades\Attachments;
use Actengage\Mailbox\Jobs\ProcessUrlAsAttachment;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use cardinalby\ContentDisposition\ContentDisposition;
use Illuminate\Http\Client\Response;

it('creates an attachment when the response has a content-disposition attachment header', function (): void {
    $message = MailboxMessage::factory()->create();

    $disposition = ContentDisposition::parse('attachment; filename="test.pdf"');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('header')
        ->with('Content-Disposition')
        ->andReturn('attachment; filename="test.pdf"');

    Attachments::shouldReceive('get')
        ->with('https://example.com/file.pdf')
        ->andReturn($response);

    Attachments::shouldReceive('contentDisposition')
        ->with($response)
        ->andReturn($disposition);

    $attachment = MailboxMessageAttachment::factory()->create([
        'message_id' => $message,
    ]);

    Attachments::shouldReceive('createFromResponse')
        ->with($message, $response, $disposition)
        ->once()
        ->andReturn($attachment);

    (new ProcessUrlAsAttachment($message, 'https://example.com/file.pdf'))->handle();
});

it('skips when the response has no content-disposition header', function (): void {
    $message = MailboxMessage::factory()->create();

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('header')
        ->with('Content-Disposition')
        ->andReturn(null);

    Attachments::shouldReceive('get')
        ->with('https://example.com/page')
        ->andReturn($response);

    Attachments::shouldReceive('createFromResponse')->never();

    (new ProcessUrlAsAttachment($message, 'https://example.com/page'))->handle();
});

it('skips when the content-disposition type is not attachment', function (): void {
    $message = MailboxMessage::factory()->create();

    $disposition = ContentDisposition::parse('inline; filename="image.jpg"');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('header')
        ->with('Content-Disposition')
        ->andReturn('inline; filename="image.jpg"');

    Attachments::shouldReceive('get')
        ->with('https://example.com/image.jpg')
        ->andReturn($response);

    Attachments::shouldReceive('contentDisposition')
        ->with($response)
        ->andReturn($disposition);

    Attachments::shouldReceive('createFromResponse')->never();

    (new ProcessUrlAsAttachment($message, 'https://example.com/image.jpg'))->handle();
});
