<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Jobs\FinishedProcessingUrlsAsAttachments;
use Actengage\Mailbox\Jobs\ProcessUrlAsAttachment;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use cardinalby\ContentDisposition\ContentDisposition;
use Dom\HTMLDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Microsoft\Graph\Generated\Models\Attachment;

class AttachmentService
{
    public function __construct(
        public ClientService $client
    ) {
        //
    }

    /**
     * Perform a GET request for the given URL.
     */
    public function get(string $url): Response
    {
        return Http::get($url);
    }

    /**
     * Extract the URLs from the given message.
     *
     * @return array<int, string>
     */
    public function extractUrls(MailboxMessage $message): array
    {
        $document = HTMLDocument::createFromString((string) $message->body, LIBXML_HTML_NOIMPLIED);

        $urls = [];

        foreach ($document->querySelectorAll('a') as $node) {
            $href = $node->getAttribute('href');
            if ($href === null) {
                continue;
            }
            if ($href === '') {
                continue;
            }

            $scheme = parse_url($href, PHP_URL_SCHEME);

            if (is_string($scheme) && in_array($scheme, ['http', 'https'], true)) {
                $urls[] = $href;
            }
        }

        return $urls;
    }

    /**
     * Determines if the given message should process urls as attachments.
     */
    public function shouldProcessUrlsAsAttachments(MailboxMessage $message): bool
    {
        if (! $message->from) {
            return false;
        }

        $mailboxes = config()->array('mailbox.mailboxes');

        /** @var array<string, mixed>|null $mailboxConfig */
        $mailboxConfig = $mailboxes[$message->mailbox->email] ?? null;

        if (! $mailboxConfig) {
            return false;
        }

        /** @var array{enabled: bool, pattern: string|null} $config */
        $config = array_merge([
            'enabled' => false,
            'pattern' => null,
        ], (array) data_get($mailboxConfig, 'process_urls_as_attachments'));

        if (! $config['enabled']) {
            return false;
        }

        if (! $config['pattern']) {
            return true;
        }

        return (bool) preg_match($config['pattern'], (string) $message->from->email);
    }

    /**
     * Dispatch the jobs to process the URLs as attachments.
     */
    public function processUrlsAsAttachments(MailboxMessage $message): void
    {
        if (! $this->shouldProcessUrlsAsAttachments($message)) {
            dispatch(new FinishedProcessingUrlsAsAttachments($message));

            return;
        }

        /** @var Collection<int, ShouldQueue> */
        $jobs = collect($this->extractUrls($message))->map(
            fn (string $url): ProcessUrlAsAttachment => new ProcessUrlAsAttachment($message, $url)
        );

        $jobs->push(new FinishedProcessingUrlsAsAttachments($message));

        Bus::chain($jobs)->catch(function () use ($message): void {
            dispatch(new FinishedProcessingUrlsAsAttachments($message));
        })->dispatch();
    }

    /**
     * Get the content disposition from the given response.
     */
    public function contentDisposition(Response $response): ContentDisposition
    {
        return ContentDisposition::parse($response->header('content-disposition'));
    }

    /**
     * Create an attachment model from the given HTTP response.
     */
    public function createFromResponse(MailboxMessage $message, Response $response, ?ContentDisposition $disposition = null): MailboxMessageAttachment
    {
        $disposition ??= ContentDisposition::parse(
            $response->header('content-disposition')
        );

        $filename = $disposition->getFilename() ?? '';
        $existing = $message->attachments()->name($filename)->first();

        if ($existing instanceof MailboxMessageAttachment) {
            return $existing;
        }

        /** @var string $disk */
        $disk = $this->client->config('storage_disk', 'local');
        $size = $response->toPsrResponse()->getBody()->getSize();
        $type = mime_content_type($response->resource());
        $path = $message->attachmentRelativePath($filename);

        /** @var string $visibility */
        $visibility = $this->client->config('storage_visibility', 'private');
        Storage::disk($disk)->put($path, $response->toPsrResponse()->getBody(), [
            'visibility' => $visibility,
        ]);

        $model = $message->attachments()->make([
            'disk' => $disk,
            'name' => $disposition->getFilename(),
            'size' => $size,
            'content_type' => $type,
            'path' => $path,
            'last_modified_at' => now(),
        ]);

        $model->mailbox()->associate($message->mailbox_id);
        $model->save();

        return $model;
    }

    /**
     * Create an attachment model from given Graph API model.
     */
    public function createFromAttachment(MailboxMessage $message, Attachment $attachment): MailboxMessageAttachment
    {
        $name = (string) $attachment->getName();
        $existing = $message->attachments()->name($name)->first();

        if ($existing instanceof MailboxMessageAttachment) {
            return $existing;
        }

        /** @var string $disk */
        $disk = $this->client->config('storage_disk', 'local');
        /** @var string $contentBytes */
        $contentBytes = $attachment->getBackingStore()->get('contentBytes');
        $contents = base64_decode($contentBytes);
        $path = $message->attachmentRelativePath($name);

        /** @var string $visibility */
        $visibility = $this->client->config('storage_visibility', 'private');
        Storage::disk($disk)->put($path, $contents, [
            'visibility' => $visibility,
        ]);

        $model = $message->attachments()->make([
            'disk' => $disk,
            'path' => $path,
            'name' => $attachment->getName(),
            'size' => $attachment->getSize(),
            'content_type' => $attachment->getContentType(),
            'last_modified_at' => $attachment->getLastModifiedDateTime(),
        ]);

        $model->mailbox()->associate($message->mailbox);
        $model->save();

        return $model;
    }
}
