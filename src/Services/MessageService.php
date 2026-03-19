<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Data\Conditional;
use Actengage\Mailbox\Data\Filter;
use Actengage\Mailbox\Facades\Attachments;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Facades\Models;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Exception;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Microsoft\Graph\BatchRequestBuilder;
use Microsoft\Graph\Core\Requests\BatchRequestContent;
use Microsoft\Graph\Core\Requests\BatchResponseContent;
use Microsoft\Graph\Core\Requests\BatchResponseItem;
use Microsoft\Graph\Core\Tasks\PageIterator;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\MessageCollectionResponse;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\CreateForward\CreateForwardPostRequestBody;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\CreateReply\CreateReplyPostRequestBody;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\CreateReplyAll\CreateReplyAllPostRequestBody;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\Move\MovePostRequestBody;
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Psr\Http\Message\StreamInterface;

class MessageService
{
    public function __construct(
        protected ClientService $service
    ) {
        //
    }

    /**
     * Find a message by the given user and message id.
     *
     * @return Promise<Message|null>
     */
    public function find(string $userId, string $messageId): Promise
    {
        return $this->service->client()->users()
            ->byUserId($userId)
            ->messages()
            ->byMessageId($messageId)
            ->get(new MessageItemRequestBuilderGetRequestConfiguration(
                queryParameters: new MessageItemRequestBuilderGetQueryParameters(
                    expand: ['attachments']
                )
            ));
    }

    /**
     * Uses a PageIterator to all the messages in the mailbox, which calls the
     * iterator function for each message that is received. This function does
     * not return anything, since the amount of memory returned could exceed
     * what is available.
     *
     * @param  callable(Message): void  $iterator
     *
     * @throws Exception
     */
    public function all(string $userId, callable $iterator, Conditional|Filter|string|null $filter = null): void
    {
        /** @var MessageCollectionResponse $response */
        $response = $this->service->client()->users()
            ->byUserId($userId)
            ->messages()
            ->get(new MessagesRequestBuilderGetRequestConfiguration(
                queryParameters: new MessagesRequestBuilderGetQueryParameters(
                    expand: ['attachments'],
                    filter: (string) $filter,
                    orderby: ['receivedDateTime asc'],
                    select: [
                        'id',
                        'subject',
                        'bodyPreview',
                        'body',
                        'sender',
                        'from',
                        'toRecipients',
                        'ccRecipients',
                        'bccRecipients',
                        'replyTo',
                        'conversationId',
                        'conversationIndex',
                        'internetMessageId',
                        'internetMessageHeaders',
                        'isRead',
                        'isDraft',
                        'importance',
                        'flag',
                        'hasAttachments',
                        'receivedDateTime',
                        'sentDateTime',
                        'createdDateTime',
                        'lastModifiedDateTime',
                        'inferenceClassification',
                        'webLink',
                        'parentFolderId',
                        'categories',
                    ],
                    top: 100
                )
            ))
            ->wait();

        /** @var PageIterator<Message> $pageIterator */
        $pageIterator = new PageIterator(
            $response, $this->service->client()->getRequestAdapter()
        );

        $pageIterator->iterate(
            /** @param array<mixed>|object $item */
            function (array|object $item) use ($iterator): bool {
                if ($item instanceof Message) {
                    $iterator($item);
                }

                return true;
            }
        );
    }

    /**
     * Create a draft reply using the Graph API.
     *
     * @return Promise<MailboxMessage|null>
     */
    public function create(Mailbox $mailbox): Promise
    {
        $draft = new Message;
        $draft->setIsDraft(true);

        return $this->service->client()->users()
            ->byUserId($mailbox->email)
            ->messages()
            ->post($draft)
            ->then(fn (?Message $model): ?MailboxMessage => $model instanceof Message ? $this->save($mailbox, $model) : null);
    }

    /**
     * Create a draft reply using the Graph API.
     *
     * @return Promise<MailboxMessage|null>
     */
    public function createReply(MailboxMessage $message): Promise
    {
        $draft = new Message;
        $draft->setSubject($message->subject);
        $draft->setIsDraft(true);

        $request = new CreateReplyPostRequestBody;
        $request->setMessage($draft);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId((string) $message->external_id)
            ->createReply()
            ->post($request)
            ->then(fn (?Message $model): ?MailboxMessage => $model instanceof Message ? $this->save($message->mailbox, $model) : null);
    }

    /**
     * Create a draft reply all using the Graph API.
     *
     * @return Promise<MailboxMessage|null>
     */
    public function createReplyAll(MailboxMessage $message): Promise
    {
        $draft = new Message;
        $draft->setSubject($message->subject);
        $draft->setIsDraft(true);

        $request = new CreateReplyAllPostRequestBody;
        $request->setMessage($draft);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId((string) $message->external_id)
            ->createReplyAll()
            ->post($request)
            ->then(fn (?Message $model): ?MailboxMessage => $model instanceof Message ? $this->save($message->mailbox, $model) : null);
    }

    /**
     * Create a draft forward using the Graph API.
     *
     * @return Promise<MailboxMessage|null>
     */
    public function createForward(MailboxMessage $message): Promise
    {
        $draft = new Message;
        $draft->setSubject($message->subject);
        $draft->setIsDraft(true);

        $request = new CreateForwardPostRequestBody;
        $request->setMessage($draft);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId((string) $message->external_id)
            ->createForward()
            ->post($request)
            ->then(fn (?Message $model): ?MailboxMessage => $model instanceof Message ? $this->save($message->mailbox, $model) : null);
    }

    /**
     * Patch the message using the Graph API.
     *
     * @return Promise<Message|null>
     */
    public function patch(MailboxMessage $message): Promise
    {
        $model = Models::makeMessageModel($message);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId((string) $message->external_id)
            ->patch($model);
    }

    /**
     * Move the message using the Graph API.
     *
     * @return Promise<Message|null>
     */
    public function move(MailboxMessage $message, MailboxFolder $folder): Promise
    {
        $moveRequest = new MovePostRequestBody;
        $moveRequest->setDestinationId((string) $folder->external_id);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId((string) $message->external_id)
            ->move()
            ->post($moveRequest);
    }

    /**
     * Delete the message using the Graph API.
     *
     * @return Collection<int, ODataError>
     */
    public function delete(MailboxMessage ...$message): Collection
    {
        $requests = collect($message)->chunk(20)->map(fn (Collection $chunk): BatchRequestContent => new BatchRequestContent(collect($chunk)->map(fn (MailboxMessage $message): RequestInformation => $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId((string) $message->external_id)
            ->toDeleteRequestInformation())->all()));

        $requestBuilder = new BatchRequestBuilder(
            $this->service->client()->getRequestAdapter()
        );

        /** @var Collection<int, ODataError> */
        return $requests->map(fn (BatchRequestContent $content) => $requestBuilder->postAsync($content)->then(fn (?BatchResponseContent $response) => $response instanceof BatchResponseContent ? collect($response->getResponses())->map(function (BatchResponseItem $item) use ($response): ?ODataError {
            $id = $item->getId();

            if ($id === null || ! $item->getBody() instanceof StreamInterface) {
                return null;
            }

            /** @var ODataError|null */
            return $response->getResponseBody($id, ODataError::class);
        })->filter()->values() : collect())->wait())->flatten();
    }

    /**
     * Send the message.
     *
     * @return Promise<void|null>
     */
    public function send(MailboxMessage $message): Promise
    {
        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId((string) $message->external_id)
            ->send()
            ->post();
    }

    /**
     * Get a specific internet message header from the given message.
     */
    public function getInternetMessageHeader(string $key, Message $message): ?string
    {
        if ($headers = $message->getInternetMessageHeaders()) {
            foreach ($headers as $header) {
                if (strtolower((string) $header->getName()) === $key) {
                    return $header->getValue();
                }
            }
        }

        return null;
    }

    /**
     * Save the message to the database.
     */
    public function save(Mailbox $mailbox, Message $message): MailboxMessage
    {
        $model = $mailbox->messages()->firstOrNew([
            'external_id' => $message->getId(),
        ]);

        $model->fill([
            'conversation_id' => $message->getConversationId(),
            'conversation_index' => (string) $message->getConversationIndex(),
            'internet_message_id' => $message->getInternetMessageId(),
            'in_reply_to' => $this->getInternetMessageHeader('in-reply-to', $message),
            'references' => $this->getInternetMessageHeader('references', $message),
            'is_read' => $message->getIsRead(),
            'is_draft' => $message->getIsDraft(),
            'flag' => $message->getFlag(),
            'importance' => $message->getImportance(),
            'subject' => $message->getSubject(),
            'from' => $message->getFrom(),
            'to' => $message->getToRecipients(),
            'cc' => $message->getCcRecipients(),
            'bcc' => $message->getBccRecipients(),
            'reply_to' => $message->getReplyTo(),
            'body' => $message->getBody(),
            'body_preview' => $message->getBodyPreview(),
            'received_at' => $message->getReceivedDateTime(),
            'sent_at' => $message->getSentDateTime(),
        ]);

        if ($message->getParentFolderId()) {
            $existingFolder = MailboxFolder::query()->externalId($message->getParentFolderId())->first();

            if ($existingFolder instanceof MailboxFolder) {
                $model->folder()->associate($existingFolder);
            } else {
                Folders::find($mailbox, $message->getParentFolderId())
                    ->then(function (?MailFolder $folder) use ($model, $mailbox): void {
                        if ($folder instanceof MailFolder) {
                            $model->folder()->associate(Folders::save($mailbox, $folder));
                        }
                    });
            }
        }

        $model->save();

        if ($message->getHasAttachments()) {
            foreach ((array) $message->getAttachments() as $attachment) {
                Attachments::createFromAttachment($model, $attachment);
            }
        }

        return $model;
    }
}
