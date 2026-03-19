<?php

use Actengage\Mailbox\Facades\Attachments;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Facades\Models;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Services\ClientService;
use Actengage\Mailbox\Services\MessageService;
use GuzzleHttp\Psr7\Utils;
use Http\Promise\FulfilledPromise;
use Illuminate\Support\Collection;
use Microsoft\Graph\Core\Requests\BatchResponseContent;
use Microsoft\Graph\Core\Requests\BatchResponseItem;
use Microsoft\Graph\Generated\Models\Attachment;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\FollowupFlag;
use Microsoft\Graph\Generated\Models\FollowupFlagStatus;
use Microsoft\Graph\Generated\Models\InternetMessageHeader;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\MessageCollectionResponse;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\CreateForward\CreateForwardRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\CreateReply\CreateReplyRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\CreateReplyAll\CreateReplyAllRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\MessageItemRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\Move\MoveRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\Send\SendRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\UserItemRequestBuilder;
use Microsoft\Graph\Generated\Users\UsersRequestBuilder;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriterFactory;
use Microsoft\Kiota\Abstractions\Store\BackingStore;

// ──────────────────────────────────────────────────────────────────────────────
// save() tests
// ──────────────────────────────────────────────────────────────────────────────

it('saves a message to the database', function (): void {
    $mailbox = Mailbox::factory()->create();

    $from = new EmailAddress;
    $from->setAddress('sender@test.com');
    $from->setName('Sender');
    $fromRecipient = new Recipient;
    $fromRecipient->setEmailAddress($from);

    $to = new EmailAddress;
    $to->setAddress('recipient@test.com');
    $toRecipient = new Recipient;
    $toRecipient->setEmailAddress($to);

    $body = new ItemBody;
    $body->setContent('<p>Hello</p>');
    $body->setContentType(new BodyType('HTML'));

    $graphMessage = new Message;
    $graphMessage->setId('msg-ext-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setConversationIndex(Utils::streamFor('conv-index'));
    $graphMessage->setInternetMessageId('<msg@test.com>');
    $graphMessage->setIsRead(true);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setSubject('Test Subject');
    $graphMessage->setFrom($fromRecipient);
    $graphMessage->setToRecipients([$toRecipient]);
    $graphMessage->setCcRecipients([]);
    $graphMessage->setBccRecipients([]);
    $graphMessage->setReplyTo([]);
    $graphMessage->setBody($body);
    $graphMessage->setBodyPreview('Hello');
    $graphMessage->setReceivedDateTime(new DateTime('2025-01-01T00:00:00Z'));
    $graphMessage->setSentDateTime(new DateTime('2025-01-01T00:00:00Z'));
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model)->toBeInstanceOf(MailboxMessage::class);
    expect($model->exists)->toBeTrue();
    expect($model->external_id)->toBe('msg-ext-id');
    expect($model->subject)->toBe('Test Subject');
    expect($model->is_read)->toBeTrue();
    expect($model->is_draft)->toBeFalse();
    expect($model->mailbox_id)->toBe($mailbox->id);
    expect($model->conversation_id)->toBe('conv-id');
    expect($model->internet_message_id)->toBe('<msg@test.com>');
    expect($model->body_preview)->toBe('Hello');
});

it('saves a message with an existing folder', function (): void {
    $mailbox = Mailbox::factory()->create();
    $folder = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'external_id' => 'folder-ext-id',
    ]);

    $graphMessage = new Message;
    $graphMessage->setId('msg-ext-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('Test');
    $graphMessage->setIsRead(false);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId('folder-ext-id');

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model->folder_id)->toBe($folder->id);
});

it('saves a message with attachments', function (): void {
    $mailbox = Mailbox::factory()->create();

    $attachment = new Attachment;
    $attachment->setName('file.pdf');
    $attachment->setContentType('application/pdf');
    $attachment->setSize(1024);

    $graphMessage = new Message;
    $graphMessage->setId('msg-ext-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('With Attachment');
    $graphMessage->setIsRead(false);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(true);
    $graphMessage->setAttachments([$attachment]);
    $graphMessage->setParentFolderId(null);

    Attachments::shouldReceive('createFromAttachment')->once();

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model)->toBeInstanceOf(MailboxMessage::class);
    expect($model->external_id)->toBe('msg-ext-id');
});

it('saves a message with unknown folder and resolves via Folders facade', function (): void {
    $mailbox = Mailbox::factory()->create();

    $graphFolder = new MailFolder;
    $graphFolder->setId('new-folder-id');
    $graphFolder->setDisplayName('New Folder');

    // Create the folder with a placeholder external_id so the DB lookup for
    // 'new-folder-id' returns null, forcing the else branch (Folders::find / Folders::save).
    // After save() the folder's external_id will be updated by the Folders::save mock.
    $savedFolder = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'external_id' => 'placeholder-id',
    ]);

    Folders::shouldReceive('find')
        ->with($mailbox, 'new-folder-id')
        ->andReturn(new FulfilledPromise($graphFolder));

    Folders::shouldReceive('save')
        ->with($mailbox, $graphFolder)
        ->andReturn($savedFolder);

    $graphMessage = new Message;
    $graphMessage->setId('msg-ext-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('Test');
    $graphMessage->setIsRead(false);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId('new-folder-id');

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model->folder_id)->toBe($savedFolder->id);
});

it('updates an existing message on save when external_id matches', function (): void {
    $mailbox = Mailbox::factory()->create();

    $existing = MailboxMessage::factory()->create([
        'mailbox_id' => $mailbox->id,
        'external_id' => 'msg-ext-id',
        'subject' => 'Old Subject',
        'is_read' => false,
    ]);

    $graphMessage = new Message;
    $graphMessage->setId('msg-ext-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('Updated Subject');
    $graphMessage->setIsRead(true);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model->id)->toBe($existing->id);
    expect($model->subject)->toBe('Updated Subject');
    expect($model->is_read)->toBeTrue();
});

// ──────────────────────────────────────────────────────────────────────────────
// getInternetMessageHeader() tests
// ──────────────────────────────────────────────────────────────────────────────

it('gets internet message header by key', function (): void {
    $header1 = new InternetMessageHeader;
    $header1->setName('In-Reply-To');
    $header1->setValue('<original@test.com>');

    $header2 = new InternetMessageHeader;
    $header2->setName('References');
    $header2->setValue('<ref@test.com>');

    $graphMessage = new Message;
    $graphMessage->setInternetMessageHeaders([$header1, $header2]);

    $service = new MessageService(Mockery::mock(ClientService::class));

    expect($service->getInternetMessageHeader('in-reply-to', $graphMessage))->toBe('<original@test.com>');
    expect($service->getInternetMessageHeader('references', $graphMessage))->toBe('<ref@test.com>');
    expect($service->getInternetMessageHeader('x-missing', $graphMessage))->toBeNull();
});

it('returns null for header when message has no headers', function (): void {
    $graphMessage = new Message;

    $service = new MessageService(Mockery::mock(ClientService::class));

    expect($service->getInternetMessageHeader('in-reply-to', $graphMessage))->toBeNull();
});

// ──────────────────────────────────────────────────────────────────────────────
// find() test
// ──────────────────────────────────────────────────────────────────────────────

it('finds a message by user and message id', function (): void {
    $graphMessage = new Message;
    $graphMessage->setId('msg-id');

    $promise = new FulfilledPromise($graphMessage);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('get')->once()->andReturn($promise);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with('msg-id')->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->find('user@test.com', 'msg-id');

    expect($result->wait())->toBe($graphMessage);
});

// ──────────────────────────────────────────────────────────────────────────────
// create() tests
// ──────────────────────────────────────────────────────────────────────────────

it('creates a draft message', function (): void {
    $mailbox = Mailbox::factory()->create();

    $graphMessage = new Message;
    $graphMessage->setId('draft-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setIsDraft(true);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setSubject(null);
    $graphMessage->setIsRead(false);
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);

    $promise = new FulfilledPromise($graphMessage);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('post')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->create($mailbox);

    $savedMessage = $result->wait();
    expect($savedMessage)->toBeInstanceOf(MailboxMessage::class);
    expect($savedMessage->external_id)->toBe('draft-id');
    expect($savedMessage->mailbox_id)->toBe($mailbox->id);
});

it('returns null from create when Graph returns null', function (): void {
    $mailbox = Mailbox::factory()->create();

    $promise = new FulfilledPromise(null);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('post')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->create($mailbox);

    expect($result->wait())->toBeNull();
});

// ──────────────────────────────────────────────────────────────────────────────
// createReply() test
// ──────────────────────────────────────────────────────────────────────────────

it('creates a draft reply', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $graphMessage = new Message;
    $graphMessage->setId('reply-draft-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setIsDraft(true);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setSubject('Re: '.$message->subject);
    $graphMessage->setIsRead(false);
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);

    $promise = new FulfilledPromise($graphMessage);

    $createReplyBuilder = Mockery::mock(CreateReplyRequestBuilder::class);
    $createReplyBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('createReply')->once()->andReturn($createReplyBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->createReply($message);

    $savedMessage = $result->wait();
    expect($savedMessage)->toBeInstanceOf(MailboxMessage::class);
    expect($savedMessage->external_id)->toBe('reply-draft-id');
});

// ──────────────────────────────────────────────────────────────────────────────
// createReplyAll() test
// ──────────────────────────────────────────────────────────────────────────────

it('creates a draft reply all', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $graphMessage = new Message;
    $graphMessage->setId('reply-all-draft-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setIsDraft(true);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setSubject('Re: '.$message->subject);
    $graphMessage->setIsRead(false);
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);

    $promise = new FulfilledPromise($graphMessage);

    $createReplyAllBuilder = Mockery::mock(CreateReplyAllRequestBuilder::class);
    $createReplyAllBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('createReplyAll')->once()->andReturn($createReplyAllBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->createReplyAll($message);

    $savedMessage = $result->wait();
    expect($savedMessage)->toBeInstanceOf(MailboxMessage::class);
    expect($savedMessage->external_id)->toBe('reply-all-draft-id');
});

// ──────────────────────────────────────────────────────────────────────────────
// createForward() test
// ──────────────────────────────────────────────────────────────────────────────

it('creates a draft forward', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $graphMessage = new Message;
    $graphMessage->setId('forward-draft-id');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setIsDraft(true);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setSubject('Fw: '.$message->subject);
    $graphMessage->setIsRead(false);
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);

    $promise = new FulfilledPromise($graphMessage);

    $createForwardBuilder = Mockery::mock(CreateForwardRequestBuilder::class);
    $createForwardBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('createForward')->once()->andReturn($createForwardBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->createForward($message);

    $savedMessage = $result->wait();
    expect($savedMessage)->toBeInstanceOf(MailboxMessage::class);
    expect($savedMessage->external_id)->toBe('forward-draft-id');
});

// ──────────────────────────────────────────────────────────────────────────────
// patch() test
// ──────────────────────────────────────────────────────────────────────────────

it('patches a message via the Graph API', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $graphModel = new Message;
    $graphModel->setId((string) $message->external_id);

    Models::shouldReceive('makeMessageModel')
        ->with(Mockery::on(fn ($arg) => $arg->id === $message->id))
        ->once()
        ->andReturn($graphModel);

    $patchedMessage = new Message;
    $patchedMessage->setId((string) $message->external_id);
    $promise = new FulfilledPromise($patchedMessage);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('patch')->with($graphModel)->once()->andReturn($promise);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->patch($message);

    expect($result->wait())->toBeInstanceOf(Message::class);
});

// ──────────────────────────────────────────────────────────────────────────────
// move() test
// ──────────────────────────────────────────────────────────────────────────────

it('moves a message to a folder via the Graph API', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);
    $folder = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'external_id' => 'target-folder-id',
    ]);

    $movedMessage = new Message;
    $movedMessage->setId((string) $message->external_id);
    $promise = new FulfilledPromise($movedMessage);

    $moveBuilder = Mockery::mock(MoveRequestBuilder::class);
    $moveBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('move')->once()->andReturn($moveBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->move($message, $folder);

    expect($result->wait())->toBeInstanceOf(Message::class);
});

// ──────────────────────────────────────────────────────────────────────────────
// send() test
// ──────────────────────────────────────────────────────────────────────────────

it('sends a message via the Graph API', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $promise = new FulfilledPromise(null);

    $sendBuilder = Mockery::mock(SendRequestBuilder::class);
    $sendBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('send')->once()->andReturn($sendBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->send($message);

    expect($result->wait())->toBeNull();
});

// ──────────────────────────────────────────────────────────────────────────────
// save() edge cases
// ──────────────────────────────────────────────────────────────────────────────

it('saves in-reply-to and references from internet message headers', function (): void {
    $mailbox = Mailbox::factory()->create();

    $header1 = new InternetMessageHeader;
    $header1->setName('In-Reply-To');
    $header1->setValue('<original@test.com>');

    $header2 = new InternetMessageHeader;
    $header2->setName('References');
    $header2->setValue('<ref1@test.com> <ref2@test.com>');

    $graphMessage = new Message;
    $graphMessage->setId('msg-with-headers');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('Reply Message');
    $graphMessage->setIsRead(true);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);
    $graphMessage->setInternetMessageHeaders([$header1, $header2]);

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model->in_reply_to)->toBe('<original@test.com>');
    expect($model->references)->toBe('<ref1@test.com> <ref2@test.com>');
});

it('saves a message with multiple attachments', function (): void {
    $mailbox = Mailbox::factory()->create();

    $attachment1 = new Attachment;
    $attachment1->setName('file1.pdf');
    $attachment1->setContentType('application/pdf');
    $attachment1->setSize(1024);

    $attachment2 = new Attachment;
    $attachment2->setName('file2.jpg');
    $attachment2->setContentType('image/jpeg');
    $attachment2->setSize(2048);

    $graphMessage = new Message;
    $graphMessage->setId('msg-multi-attach');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('Multiple Attachments');
    $graphMessage->setIsRead(false);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(true);
    $graphMessage->setAttachments([$attachment1, $attachment2]);
    $graphMessage->setParentFolderId(null);

    Attachments::shouldReceive('createFromAttachment')->twice();

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model)->toBeInstanceOf(MailboxMessage::class);
});

it('does not process attachments when hasAttachments is false', function (): void {
    $mailbox = Mailbox::factory()->create();

    $graphMessage = new Message;
    $graphMessage->setId('msg-no-attach');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('No Attachments');
    $graphMessage->setIsRead(false);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId(null);

    Attachments::shouldReceive('createFromAttachment')->never();

    $service = new MessageService(Mockery::mock(ClientService::class));
    $service->save($mailbox, $graphMessage);
});

// ──────────────────────────────────────────────────────────────────────────────
// all() tests
// ──────────────────────────────────────────────────────────────────────────────

it('iterates all messages for a user', function (): void {
    $msg1 = new Message;
    $msg1->setId('msg-1');
    $msg1->setSubject('Message 1');

    $msg2 = new Message;
    $msg2->setId('msg-2');
    $msg2->setSubject('Message 2');

    $collectionResponse = Mockery::mock(MessageCollectionResponse::class);
    $collectionResponse->shouldReceive('getValue')->andReturn([$msg1, $msg2]);
    $collectionResponse->shouldReceive('getOdataNextLink')->andReturn(null);
    $collectionResponse->shouldReceive('getBackingStore')->andReturn(
        Mockery::mock(BackingStore::class)
            ->shouldReceive('get')->andReturn(null)
            ->getMock()
    );

    $promise = new FulfilledPromise($collectionResponse);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('get')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($userBuilder);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn(new FulfilledPromise(null));

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $collected = [];
    $service = new MessageService($clientService);
    $service->all('user@test.com', function (Message $message) use (&$collected): void {
        $collected[] = $message->getId();
    });

    expect($collected)->toBe(['msg-1', 'msg-2']);
});

it('iterates all messages with a filter string', function (): void {
    $msg = new Message;
    $msg->setId('msg-filtered');

    $collectionResponse = Mockery::mock(MessageCollectionResponse::class);
    $collectionResponse->shouldReceive('getValue')->andReturn([$msg]);
    $collectionResponse->shouldReceive('getOdataNextLink')->andReturn(null);
    $collectionResponse->shouldReceive('getBackingStore')->andReturn(
        Mockery::mock(BackingStore::class)
            ->shouldReceive('get')->andReturn(null)
            ->getMock()
    );

    $promise = new FulfilledPromise($collectionResponse);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('get')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($userBuilder);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn(new FulfilledPromise(null));

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $collected = [];
    $service = new MessageService($clientService);
    $service->all('user@test.com', function (Message $message) use (&$collected): void {
        $collected[] = $message->getId();
    }, 'isRead eq false');

    expect($collected)->toBe(['msg-filtered']);
});

// ──────────────────────────────────────────────────────────────────────────────
// delete() tests
// ──────────────────────────────────────────────────────────────────────────────

it('deletes messages via the Graph API batch request', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $requestInfo = new RequestInformation;
    $requestInfo->httpMethod = HttpMethod::DELETE;
    $requestInfo->setUri('https://graph.microsoft.com/v1.0/users/'.$mailbox->email.'/messages/'.$message->external_id);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('toDeleteRequestInformation')->once()->andReturn($requestInfo);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $batchResponseItem = Mockery::mock(BatchResponseItem::class);
    $batchResponseItem->shouldReceive('getId')->andReturn(null);
    $batchResponseItem->shouldReceive('getBody')->andReturn(null);

    $batchResponseContent = Mockery::mock(BatchResponseContent::class);
    $batchResponseContent->shouldReceive('getResponses')->andReturn([$batchResponseItem]);

    $batchPromise = new FulfilledPromise($batchResponseContent);

    $serializationWriter = Mockery::mock(SerializationWriter::class);
    $serializationWriter->shouldReceive('writeObjectValue')->andReturnNull();
    $serializationWriter->shouldReceive('getSerializedContent')->andReturn(Utils::streamFor('{}'));

    $serializationWriterFactory = Mockery::mock(SerializationWriterFactory::class);
    $serializationWriterFactory->shouldReceive('getSerializationWriter')->andReturn($serializationWriter);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn($batchPromise);
    $requestAdapter->shouldReceive('getSerializationWriterFactory')->andReturn($serializationWriterFactory);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->delete($message);

    expect($result)->toBeInstanceOf(Collection::class);
});

it('returns ODataErrors from batch delete when response items have valid id and body', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $requestInfo = new RequestInformation;
    $requestInfo->httpMethod = HttpMethod::DELETE;
    $requestInfo->setUri('https://graph.microsoft.com/v1.0/users/'.$mailbox->email.'/messages/'.$message->external_id);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('toDeleteRequestInformation')->once()->andReturn($requestInfo);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $batchResponseItem = Mockery::mock(BatchResponseItem::class);
    $batchResponseItem->shouldReceive('getId')->andReturn('item-1');
    $batchResponseItem->shouldReceive('getBody')->andReturn(Utils::streamFor('{"error": {"code": "ErrorItemNotFound"}}'));

    $odataError = new \Microsoft\Graph\Generated\Models\ODataErrors\ODataError;

    $batchResponseContent = Mockery::mock(BatchResponseContent::class);
    $batchResponseContent->shouldReceive('getResponses')->andReturn([$batchResponseItem]);
    $batchResponseContent->shouldReceive('getResponseBody')
        ->with('item-1', \Microsoft\Graph\Generated\Models\ODataErrors\ODataError::class)
        ->andReturn($odataError);

    $batchPromise = new FulfilledPromise($batchResponseContent);

    $serializationWriter = Mockery::mock(SerializationWriter::class);
    $serializationWriter->shouldReceive('writeObjectValue')->andReturnNull();
    $serializationWriter->shouldReceive('getSerializedContent')->andReturn(Utils::streamFor('{}'));

    $serializationWriterFactory = Mockery::mock(SerializationWriterFactory::class);
    $serializationWriterFactory->shouldReceive('getSerializationWriter')->andReturn($serializationWriter);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn($batchPromise);
    $requestAdapter->shouldReceive('getSerializationWriterFactory')->andReturn($serializationWriterFactory);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->delete($message);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(1);
    expect($result[0])->toBeInstanceOf(\Microsoft\Graph\Generated\Models\ODataErrors\ODataError::class);
});

// ──────────────────────────────────────────────────────────────────────────────
// createReply/createReplyAll/createForward null response tests
// ──────────────────────────────────────────────────────────────────────────────

it('returns null from createReply when Graph returns null', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $promise = new FulfilledPromise(null);

    $createReplyBuilder = Mockery::mock(CreateReplyRequestBuilder::class);
    $createReplyBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('createReply')->once()->andReturn($createReplyBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->createReply($message);

    expect($result->wait())->toBeNull();
});

it('returns null from createReplyAll when Graph returns null', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $promise = new FulfilledPromise(null);

    $createReplyAllBuilder = Mockery::mock(CreateReplyAllRequestBuilder::class);
    $createReplyAllBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('createReplyAll')->once()->andReturn($createReplyAllBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->createReplyAll($message);

    expect($result->wait())->toBeNull();
});

it('returns null from createForward when Graph returns null', function (): void {
    $mailbox = Mailbox::factory()->create();
    $message = MailboxMessage::factory()->create(['mailbox_id' => $mailbox->id]);

    $promise = new FulfilledPromise(null);

    $createForwardBuilder = Mockery::mock(CreateForwardRequestBuilder::class);
    $createForwardBuilder->shouldReceive('post')->once()->andReturn($promise);

    $messageItemBuilder = Mockery::mock(MessageItemRequestBuilder::class);
    $messageItemBuilder->shouldReceive('createForward')->once()->andReturn($createForwardBuilder);

    $messagesBuilder = Mockery::mock(MessagesRequestBuilder::class);
    $messagesBuilder->shouldReceive('byMessageId')->with((string) $message->external_id)->once()->andReturn($messageItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('messages')->once()->andReturn($messagesBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new MessageService($clientService);
    $result = $service->createForward($message);

    expect($result->wait())->toBeNull();
});

it('saves a message when Folders::find returns null for unknown folder', function (): void {
    $mailbox = Mailbox::factory()->create();

    Folders::shouldReceive('find')
        ->with($mailbox, 'unknown-folder-id')
        ->andReturn(new FulfilledPromise(null));

    $graphMessage = new Message;
    $graphMessage->setId('msg-unknown-folder');
    $graphMessage->setConversationId('conv-id');
    $graphMessage->setSubject('Test');
    $graphMessage->setIsRead(false);
    $graphMessage->setIsDraft(false);
    $graphMessage->setFlag((function () {
        $flag = new FollowupFlag;
        $flag->setFlagStatus(new FollowupFlagStatus('notFlagged'));

        return $flag;
    })());
    $graphMessage->setHasAttachments(false);
    $graphMessage->setParentFolderId('unknown-folder-id');

    $service = new MessageService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphMessage);

    expect($model)->toBeInstanceOf(MailboxMessage::class);
    expect($model->folder_id)->toBeNull();
});
