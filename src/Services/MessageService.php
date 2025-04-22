<?php

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Facades\Attachments;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Facades\Models;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetQueryParameters;

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
     * @param string $userId
     * @param string $messageId
     * @return Promise<Message>
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
     * Get a all the folders as a flattened collection for the given user.
     *
     * @param string $userId
     * @return Collection<Message>
     * @throws \Exception
     */
    public function all(string $userId): Collection
    {
        $response = $this->service->client()->users()
            ->byUserId($userId)
            ->messages()
            ->get(new MessagesRequestBuilderGetRequestConfiguration(
                queryParameters: new MessagesRequestBuilderGetQueryParameters(
                    top: 200,
                    orderby: ['receivedDateTime asc'],
                    expand: ['attachments'],
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
                        'categories'
                    ]
                )
            ))
            ->wait();

        $messages = collect();

        $pageIterator = new PageIterator(
            $response, $this->service->client()->getRequestAdapter()
        );

        $pageIterator->iterate(function(Message $message) use ($messages) {
            $messages->push($message);

            return true;
        });

        return $messages;
    }

    /**
     * Create a draft reply using the Graph API.
     *
     * @return Promise<MailboxMessage>
     */
    public function create(Mailbox $mailbox): Promise
    {        
        $draft = new Message();
        $draft->setIsDraft(true);

        return $this->service->client()->users()
            ->byUserId($mailbox->email)
            ->messages()
            ->post($draft)
            ->then(function(Message $model) use ($mailbox) {
                return $this->save($mailbox, $model);
            });
    }
    
    /**
     * Create a draft reply using the Graph API.
     *
     * @return Promise<MailboxMessage>
     */
    public function createReply(MailboxMessage $message): Promise
    {        
        $draft = new Message();
        $draft->setSubject($message->subject);
        $draft->setIsDraft(true);

        $request = new CreateReplyPostRequestBody();
        $request->setMessage($draft);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId($message->external_id)
            ->createReply()
            ->post($request)
            ->then(function(Message $model) use ($message) {
                return $this->save($message->mailbox, $model);
            });
    }
    
    /**
     * Create a draft reply all using the Graph API.
     *
     * @return Promise<MailboxMessage>
     */
    public function createReplyAll(MailboxMessage $message): Promise
    {        
        $draft = new Message();
        $draft->setSubject($message->subject);
        $draft->setIsDraft(true);

        $request = new CreateReplyAllPostRequestBody();
        $request->setMessage($draft);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId($message->external_id)
            ->createReplyAll()
            ->post($request)
            ->then(function(Message $model) use ($message) {
                return $this->save($message->mailbox, $model);
            });
    }
    /**
     * Create a draft forward using the Graph API.
     *
     * @return Promise<MailboxMessage>
     */
    public function createForward(MailboxMessage $message): Promise
    {        
        $draft = new Message();
        $draft->setSubject($message->subject);
        $draft->setIsDraft(true);

        $request = new CreateForwardPostRequestBody();
        $request->setMessage($draft);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId($message->external_id)
            ->createForward()
            ->post($request)
            ->then(function(Message $model) use ($message) {
                return $this->save($message->mailbox, $model);
            });
    }

    /**
     * Patch the message using the Graph API.
     *
     * @param MailboxMessage $message
     * @return Promise<Message|null>
     */
    public function patch(MailboxMessage $message): Promise
    {
        $model = Models::makeMessageModel($message);

        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId($message->external_id)
            ->patch($model);
    }

    /**
     * Delete the message using the Graph API.
     *
     * @param MailboxMessage ...$message
     * @return \Illuminate\Support\Collection<int, ODataError>>
     */
    public function delete(MailboxMessage ...$message): Collection
    {
        $requests = collect($message)->chunk(20)->map(function(Collection $chunk) {
            return new BatchRequestContent(collect($chunk)->map(function(MailboxMessage $message) {
                return $this->service->client()->users()
                    ->byUserId($message->mailbox->email)
                    ->messages()
                    ->byMessageId($message->external_id)
                    ->toDeleteRequestInformation();
            })->all());
        });

        $requestBuilder = new BatchRequestBuilder(
            $this->service->client()->getRequestAdapter()
        );

        return $requests->map(function(BatchRequestContent $content) use ($requestBuilder) {
            return $requestBuilder->postAsync($content)->then(function (BatchResponseContent $response) {
                return collect($response->getResponses())->map(function (BatchResponseItem $item) use ($response) {
                    if($item->getBody() === null) {
                        return;
                    }

                    return $response->getResponseBody($item->getId(), ODataError::class);
                })->filter();
            })->wait();
        })->flatten();
    }

    /**
     * Send the message.
     *
     * @param MailboxMessage $message
     * @return Promise<void|null>
     */
    public function send(MailboxMessage $message): Promise
    {
        return $this->service->client()->users()
            ->byUserId($message->mailbox->email)
            ->messages()
            ->byMessageId($message->external_id)
            ->send()
            ->post();
    } 

    /**
     * Get a specific internet message header from the given message.
     * 
     * @param \Microsoft\Graph\Generated\Models\Message $message
     */
    public function getInternetMessageHeader(string $key, Message $message): ?string
    {
        if($headers = $message->getInternetMessageHeaders()) {
            foreach ($headers as $header) {
                if (strtolower($header->getName()) === $key) {
                    return $header->getValue();
                }
            }
        }

        return null;
    }

    /**
     * Save the message to the database.
     *
     * @param Mailbox $mailbox
     * @param Message $message
     * @return MailboxMessage
     */
    public function save(Mailbox $mailbox, Message $message): MailboxMessage
    {
        $model = $mailbox->messages()->firstOrNew([
            'external_id' => $message->getId()
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

        if($folder = MailboxFolder::externalId($message->getParentFolderId())->first()) {
            $model->folder()->associate($folder);
        }
        else {
            Folders::find($mailbox, $message->getParentFolderId())
                ->then(function(MailFolder $folder) use ($model, $mailbox) {
                    $model->folder()->associate(Folders::save($mailbox, $folder));
                });
        }

        $model->save();

        if($message->getHasAttachments()) {
            foreach(collect($message->getAttachments()) as $attachment) {
                Attachments::createFromAttachment($model, $attachment);
            }
        }

        return $model;
    }
}