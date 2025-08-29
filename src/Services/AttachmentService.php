<?php

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
use Pest\Support\Arr;

class AttachmentService
{
    public function __construct(
        public ClientService $client
    ) {
        //
    }

    /**
     * Perform a GET request for the given URL.
     *
     * @param string $url
     * @return Response
     */
    public function get(string $url): Response
    {
        return Http::get($url);       
    }

    /**
     * Extract the URLs from the given message.
     *
     * @param MailboxMessage $message
     * @return array<int,string>
     */
    public function extractUrls(MailboxMessage $message): array
    {
        $document = HTMLDocument::createFromString($message->body, LIBXML_HTML_NOIMPLIED);

        $urls = [];
        
        foreach($document->querySelectorAll('a') as $node) {
            if(!$href = $node->getAttribute('href')) {
                continue;
            }

            $scheme = parse_url($href, PHP_URL_SCHEME);

            if($href && in_array($scheme, ['http', 'https'])) {
                $urls[] = $href;
            }            
        }

        return $urls;
    }

    /**
     * Determines if the given message should process urls as attachments.
     * 
     * @param \Actengage\Mailbox\Models\MailboxMessage $message
     * @return bool
     */
    public function shouldProcessUrlsAsAttachments(MailboxMessage $message): bool
    {
        if(!$message->from) {
            return false;
        }

        if(!$mailboxConfig = Arr::get(
            config()->array('mailbox.mailboxes'),
            $message->mailbox->email
        )) {
            return false;
        };

        $config = array_merge([
            'enabled' => false,
            'pattern' => null
        ], Arr::get($mailboxConfig, 'process_urls_as_attachments'));

        if(!$config['enabled']) {
            return false;
        }

        if(!$config['pattern']) {
            return true;
        }

        if(!preg_match($config['pattern'], $message->from->email)) {
            return false;
        }
        
        return true;
    }

    /**
     * Dispatch the jobs to process the URLs as attachments. 
     *
     * @param MailboxMessage $message
     * @return void
     */
    public function processUrlsAsAttachments(MailboxMessage $message): void
    {
        if(!$this->shouldProcessUrlsAsAttachments($message)) {
            dispatch(new FinishedProcessingUrlsAsAttachments($message));
            
            return;
        }

        /** @var Collection<int, ShouldQueue> */
        $jobs = collect($this->extractUrls($message))->map(
            fn (string $url) => new ProcessUrlAsAttachment($message, $url)
        );

        $jobs->push(new FinishedProcessingUrlsAsAttachments($message));

        Bus::chain($jobs)->catch(function() use ($message) {
            dispatch(new FinishedProcessingUrlsAsAttachments($message));
        })->dispatch();
    }

    /**
     * Get the content disposition from the given response.
     *
     * @param Response $response
     * @return ContentDisposition
     */
    public function contentDisposition(Response $response): ContentDisposition
    {
        return ContentDisposition::parse($response->header('content-disposition'));
    }

    /**
     * Create an attachment model from the given HTTP response.
     *
     * @param MailboxMessage $message
     * @param Response $response
     * @param ContentDisposition|null $disposition
     * @return MailboxMessageAttachment
     */
    public function createFromResponse(MailboxMessage $message, Response $response, ?ContentDisposition $disposition = null): MailboxMessageAttachment
    {
        $disposition = $disposition ?? ContentDisposition::parse(
            $response->header('content-disposition')
        );

        if($existing = $message->attachments()->name($disposition->getFilename())->first()) {
            return $existing;
        }

        $disk = $this->client->config('storage_disk', 'local');
        $size = $response->getBody()->getSize();
        $type = mime_content_type($response->resource());
        $path = $message->attachmentRelativePath($disposition->getFilename());

        Storage::disk($disk)->put($path, $response->toPsrResponse()->getBody(), [
            'visibility' => $this->client->config('storage_visibility', 'private')
        ]);

        $model = $message->attachments()->make([
            'disk' => $disk,
            'name' => $disposition->getFilename(),
            'size' => $size,
            'content_type' => $type,
            'path' => $path,
            'last_modified_at' => now()
        ]);

        $model->mailbox()->associate($message->mailbox_id);
        $model->save();

        return $model;
    }

    /**
     * Create an attachment model from given Graph API model.
     *
     * @param MailboxMessage $message
     * @param Attachment $attachment
     * @return void
     */
    public function createFromAttachment(MailboxMessage $message, Attachment $attachment)
    {
        if($existing = $message->attachments()->name($attachment->getName())->first()) {
            return $existing;
        }

        $disk = $this->client->config('storage_disk', 'local');
        $contents = base64_decode($attachment->getBackingStore()->get('contentBytes'));
        $path = $message->attachmentRelativePath($attachment->getName());

        Storage::disk($disk)->put($path, $contents, [
            'visibility' => $this->client->config('storage_visibility', 'private')
        ]);

        $model = $message->attachments()->make([
            'disk' => $disk,
            'path' => $path,
            'name' => $attachment->getName(),
            'size' => $attachment->getSize(),
            'content_type' => $attachment->getContentType(),
            'last_modified_at' => $attachment->getLastModifiedDateTime()
        ]);

        $model->mailbox()->associate($message->mailbox);
        $model->save();

        return $model;
    }
}