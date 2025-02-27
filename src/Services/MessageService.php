<?php

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Microsoft\Graph\Core\Tasks\PageIterator;
use Microsoft\Graph\Generated\Models\Message;
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
     * @return Message
     */
    public function find(string $userId, string $messageId): Message
    {
        return $this->service->client()->users()
            ->byUserId($userId)
            ->messages()
            ->byMessageId($messageId)
            ->get(new MessageItemRequestBuilderGetRequestConfiguration(
                queryParameters: new MessageItemRequestBuilderGetQueryParameters(
                    expand: ['attachments']
                )
            ))
            ->wait();
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
                    top: 1000,
                    expand: ['attachments']
                )
            ))
            ->wait();

        $messages = collect();

        $pageIterator = new PageIterator(
            $response, $this->service->client()->getRequestAdapter()
        );

        while($pageIterator->hasNext()) {
            $pageIterator->iterate(function(Message $message) use ($messages) {
                $messages->push($message);
            });
        }

        return $messages;
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
            'conversation_index' => $message->getConversationIndex(),
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

        if($folder = MailboxFolder::folder($message->getParentFolderId())->first()) {
            $model->folder()->associate($folder);
        }
        else {
            $folder = Folders::find($mailbox, $message->getParentFolderId());

            $model->folder()->associate(Folders::save($mailbox, $folder));
        }

        $model->save();

        if($message->getHasAttachments()) {
            foreach(collect($message->getAttachments()) as $attachment) {
                $disk = $this->service->config('storage_disk', 'local');

                $contents = base64_decode($attachment->getBackingStore()->get('contentBytes'));

                $path = $model->attachmentRelativePath($attachment->getName());

                Storage::disk($disk)->put($path, $contents, [
                    'visibility' => 'public'
                ]);

                $attachmentModel = $model->attachments()->firstOrNew([
                    'path' => $path
                ]);

                $attachmentModel->fill([
                    'disk' => $disk,
                    'path' => $path,
                    'name' => $attachment->getName(),
                    'size' => $attachment->getSize(),
                    'content_type' => $attachment->getContentType(),
                    'last_modified_at' => $attachment->getLastModifiedDateTime()
                ]);

                $attachmentModel->mailbox()->associate($mailbox);
                $attachmentModel->save();
            }
        }

        return $model;
    }
}