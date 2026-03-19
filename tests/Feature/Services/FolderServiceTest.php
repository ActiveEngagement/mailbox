<?php

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Services\ClientService;
use Actengage\Mailbox\Services\FolderService;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Models\MailFolderCollectionResponse;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\ChildFolders\ChildFoldersRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\MailFolderItemRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilder;
use Microsoft\Graph\Generated\Users\Item\UserItemRequestBuilder;
use Microsoft\Graph\Generated\Users\UsersRequestBuilder;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\RequestAdapter;

it('saves a folder to the database', function (): void {
    $mailbox = Mailbox::factory()->create();

    $graphFolder = new MailFolder;
    $graphFolder->setId('folder-ext-id');
    $graphFolder->setDisplayName('Inbox');
    $graphFolder->setIsHidden(false);
    $graphFolder->setParentFolderId(null);
    $graphFolder->setChildFolders([]);

    $service = new FolderService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphFolder);

    expect($model)->toBeInstanceOf(MailboxFolder::class);
    expect($model->external_id)->toBe('folder-ext-id');
    expect($model->name)->toBe('Inbox');
    expect($model->is_hidden)->toBeFalse();
    expect($model->mailbox_id)->toBe($mailbox->id);
});

it('updates an existing folder', function (): void {
    $mailbox = Mailbox::factory()->create();
    $existing = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'external_id' => 'folder-ext-id',
        'name' => 'Old Name',
    ]);

    $graphFolder = new MailFolder;
    $graphFolder->setId('folder-ext-id');
    $graphFolder->setDisplayName('Updated Name');
    $graphFolder->setIsHidden(true);
    $graphFolder->setParentFolderId(null);
    $graphFolder->setChildFolders([]);

    $service = new FolderService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphFolder);

    expect($model->id)->toBe($existing->id);
    expect($model->name)->toBe('Updated Name');
    expect($model->is_hidden)->toBeTrue();
});

it('saves a folder with a parent', function (): void {
    $mailbox = Mailbox::factory()->create();
    $parent = MailboxFolder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'external_id' => 'parent-ext-id',
    ]);

    $graphFolder = new MailFolder;
    $graphFolder->setId('child-ext-id');
    $graphFolder->setDisplayName('Child');
    $graphFolder->setIsHidden(false);
    $graphFolder->setParentFolderId('parent-ext-id');
    $graphFolder->setChildFolders([]);

    $service = new FolderService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphFolder);

    expect($model->parent_id)->toBe($parent->id);
});

it('saves a folder with child folders recursively', function (): void {
    $mailbox = Mailbox::factory()->create();

    $child = new MailFolder;
    $child->setId('child-ext-id');
    $child->setDisplayName('Child');
    $child->setIsHidden(false);
    $child->setParentFolderId('parent-ext-id');
    $child->setChildFolders([]);

    $parent = new MailFolder;
    $parent->setId('parent-ext-id');
    $parent->setDisplayName('Parent');
    $parent->setIsHidden(false);
    $parent->setParentFolderId(null);
    $parent->setChildFolders([$child]);

    $service = new FolderService(Mockery::mock(ClientService::class));
    $service->save($mailbox, $parent);

    expect(MailboxFolder::query()->count())->toBe(2);

    $childModel = MailboxFolder::query()->externalId('child-ext-id')->first();
    expect($childModel)->not->toBeNull();
    expect($childModel->name)->toBe('Child');

    $parentModel = MailboxFolder::query()->externalId('parent-ext-id')->first();
    expect($parentModel)->not->toBeNull();
    expect($parentModel->name)->toBe('Parent');
});

it('finds a folder using the Graph API', function (): void {
    $mailbox = Mailbox::factory()->create();

    $graphFolder = new MailFolder;
    $graphFolder->setId('folder-id');
    $graphFolder->setDisplayName('Inbox');

    $promise = new FulfilledPromise($graphFolder);

    $folderItemBuilder = Mockery::mock(MailFolderItemRequestBuilder::class);
    $folderItemBuilder->shouldReceive('get')->once()->andReturn($promise);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('byMailFolderId')->with('folder-id')->once()->andReturn($folderItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->once()->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with($mailbox->email)->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->find($mailbox, 'folder-id');

    expect($result)->toBeInstanceOf(Promise::class);
    expect($result->wait())->toBe($graphFolder);
});

it('finds a folder by string email', function (): void {
    $graphFolder = new MailFolder;
    $graphFolder->setId('folder-id');

    $promise = new FulfilledPromise($graphFolder);

    $folderItemBuilder = Mockery::mock(MailFolderItemRequestBuilder::class);
    $folderItemBuilder->shouldReceive('get')->once()->andReturn($promise);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('byMailFolderId')->with('folder-id')->once()->andReturn($folderItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->once()->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('test@test.com')->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->find('test@test.com', 'folder-id');

    expect($result)->toBeInstanceOf(Promise::class);
    expect($result->wait())->toBe($graphFolder);
});

it('gets child folders', function (): void {
    $child = new MailFolder;
    $child->setId('child-id');
    $child->setDisplayName('Child');
    $child->setChildFolderCount(0);

    $response = new MailFolderCollectionResponse;
    $response->setValue([$child]);

    $promise = new FulfilledPromise($response);

    $childFoldersBuilder = Mockery::mock(ChildFoldersRequestBuilder::class);
    $childFoldersBuilder->shouldReceive('get')->once()->andReturn($promise);

    $folderItemBuilder = Mockery::mock(MailFolderItemRequestBuilder::class);
    $folderItemBuilder->shouldReceive('childFolders')->once()->andReturn($childFoldersBuilder);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('byMailFolderId')->with('parent-id')->once()->andReturn($folderItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->once()->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('test@test.com')->once()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->once()->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->getChildFolders('test@test.com', 'parent-id');

    expect($result)->toBeInstanceOf(MailFolderCollectionResponse::class);
    expect($result->getValue())->toHaveCount(1);
    expect($result->getValue()[0]->getId())->toBe('child-id');
});

it('gets child folders with nested children recursively', function (): void {
    $grandchild = new MailFolder;
    $grandchild->setId('grandchild-id');
    $grandchild->setDisplayName('Grandchild');
    $grandchild->setChildFolderCount(0);

    $grandchildResponse = new MailFolderCollectionResponse;
    $grandchildResponse->setValue([$grandchild]);

    $grandchildPromise = new FulfilledPromise($grandchildResponse);

    $child = new MailFolder;
    $child->setId('child-id');
    $child->setDisplayName('Child');
    $child->setChildFolderCount(1);
    $child->setChildFolders([]);

    $response = new MailFolderCollectionResponse;
    $response->setValue([$child]);

    $promise = new FulfilledPromise($response);

    // Mock for the grandchild fetch
    $grandchildChildFoldersBuilder = Mockery::mock(ChildFoldersRequestBuilder::class);
    $grandchildChildFoldersBuilder->shouldReceive('get')->once()->andReturn($grandchildPromise);

    $grandchildFolderItemBuilder = Mockery::mock(MailFolderItemRequestBuilder::class);
    $grandchildFolderItemBuilder->shouldReceive('childFolders')->once()->andReturn($grandchildChildFoldersBuilder);

    // Mock for the parent fetch
    $childFoldersBuilder = Mockery::mock(ChildFoldersRequestBuilder::class);
    $childFoldersBuilder->shouldReceive('get')->once()->andReturn($promise);

    $folderItemBuilder = Mockery::mock(MailFolderItemRequestBuilder::class);
    $folderItemBuilder->shouldReceive('childFolders')->once()->andReturn($childFoldersBuilder);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('byMailFolderId')
        ->with('parent-id')->once()->andReturn($folderItemBuilder);
    $foldersBuilder->shouldReceive('byMailFolderId')
        ->with('child-id')->once()->andReturn($grandchildFolderItemBuilder);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->twice()->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('test@test.com')->twice()->andReturn($userBuilder);

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->twice()->andReturn($usersBuilder);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->twice()->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->getChildFolders('test@test.com', 'parent-id');

    expect($result)->toBeInstanceOf(MailFolderCollectionResponse::class);
    expect($result->getValue())->toHaveCount(1);
    expect($result->getValue()[0]->getChildFolders())->toHaveCount(1);
    expect($result->getValue()[0]->getChildFolders()[0]->getId())->toBe('grandchild-id');
});

it('saves a folder without a parent when parent does not exist', function (): void {
    $mailbox = Mailbox::factory()->create();

    $graphFolder = new MailFolder;
    $graphFolder->setId('orphan-ext-id');
    $graphFolder->setDisplayName('Orphan');
    $graphFolder->setIsHidden(false);
    $graphFolder->setParentFolderId('nonexistent-parent-id');
    $graphFolder->setChildFolders([]);

    $service = new FolderService(Mockery::mock(ClientService::class));
    $model = $service->save($mailbox, $graphFolder);

    expect($model->parent_id)->toBeNull();
    expect($model->external_id)->toBe('orphan-ext-id');
});

it('saves folders with different external ids for different mailboxes', function (): void {
    $mailbox1 = Mailbox::factory()->create();
    $mailbox2 = Mailbox::factory()->create();

    $graphFolder1 = new MailFolder;
    $graphFolder1->setId('ext-id-1');
    $graphFolder1->setDisplayName('Inbox');
    $graphFolder1->setIsHidden(false);
    $graphFolder1->setParentFolderId(null);
    $graphFolder1->setChildFolders([]);

    $graphFolder2 = new MailFolder;
    $graphFolder2->setId('ext-id-2');
    $graphFolder2->setDisplayName('Inbox');
    $graphFolder2->setIsHidden(false);
    $graphFolder2->setParentFolderId(null);
    $graphFolder2->setChildFolders([]);

    $service = new FolderService(Mockery::mock(ClientService::class));
    $model1 = $service->save($mailbox1, $graphFolder1);
    $model2 = $service->save($mailbox2, $graphFolder2);

    expect($model1->mailbox_id)->toBe($mailbox1->id);
    expect($model2->mailbox_id)->toBe($mailbox2->id);
    expect($model1->id)->not->toBe($model2->id);
});

// ──────────────────────────────────────────────────────────────────────────────
// tree() tests
// ──────────────────────────────────────────────────────────────────────────────

it('gets the tree of folders for a user', function (): void {
    $folder1 = new MailFolder;
    $folder1->setId('folder-1');
    $folder1->setDisplayName('Inbox');
    $folder1->setChildFolderCount(0);
    $folder1->setChildFolders([]);

    $folder2 = new MailFolder;
    $folder2->setId('folder-2');
    $folder2->setDisplayName('Sent');
    $folder2->setChildFolderCount(0);
    $folder2->setChildFolders([]);

    $collectionResponse = new MailFolderCollectionResponse;
    $collectionResponse->setValue([$folder1, $folder2]);

    $promise = new FulfilledPromise($collectionResponse);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('get')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->once()->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($userBuilder);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn(new FulfilledPromise(null));

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->tree('user@test.com');

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result[0]->getId())->toBe('folder-1');
    expect($result[1]->getId())->toBe('folder-2');
});

it('gets tree with child folders that need fetching', function (): void {
    $child = new MailFolder;
    $child->setId('child-id');
    $child->setDisplayName('Child');
    $child->setChildFolderCount(0);
    $child->setChildFolders([]);

    $childResponse = new MailFolderCollectionResponse;
    $childResponse->setValue([$child]);

    $childPromise = new FulfilledPromise($childResponse);

    $folder = new MailFolder;
    $folder->setId('parent-id');
    $folder->setDisplayName('Parent');
    $folder->setChildFolderCount(1);
    $folder->setChildFolders([]);

    $collectionResponse = new MailFolderCollectionResponse;
    $collectionResponse->setValue([$folder]);

    $promise = new FulfilledPromise($collectionResponse);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('get')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->andReturn($userBuilder);

    // Mock for getChildFolders call
    $childFoldersBuilder = Mockery::mock(ChildFoldersRequestBuilder::class);
    $childFoldersBuilder->shouldReceive('get')->once()->andReturn($childPromise);

    $folderItemBuilder = Mockery::mock(MailFolderItemRequestBuilder::class);
    $folderItemBuilder->shouldReceive('childFolders')->once()->andReturn($childFoldersBuilder);

    $childFoldersRequestBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $childFoldersRequestBuilder->shouldReceive('byMailFolderId')->with('parent-id')->once()->andReturn($folderItemBuilder);

    $childUserBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $childUserBuilder->shouldReceive('mailFolders')->once()->andReturn($childFoldersRequestBuilder);

    $childUsersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $childUsersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($childUserBuilder);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn(new FulfilledPromise(null));

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->andReturn($usersBuilder, $childUsersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->tree('user@test.com');

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(1);
    expect($result[0]->getChildFolders())->toHaveCount(1);
    expect($result[0]->getChildFolders()[0]->getId())->toBe('child-id');
});

it('gets tree with child folders already present but needing grandchild fetching', function (): void {
    $grandchild = new MailFolder;
    $grandchild->setId('grandchild-id');
    $grandchild->setDisplayName('Grandchild');
    $grandchild->setChildFolderCount(0);

    $grandchildResponse = new MailFolderCollectionResponse;
    $grandchildResponse->setValue([$grandchild]);

    $grandchildPromise = new FulfilledPromise($grandchildResponse);

    $child = new MailFolder;
    $child->setId('child-id');
    $child->setDisplayName('Child');
    $child->setChildFolderCount(1);
    $child->setChildFolders([]);

    $parent = new MailFolder;
    $parent->setId('parent-id');
    $parent->setDisplayName('Parent');
    $parent->setChildFolderCount(1);
    $parent->setChildFolders([$child]);

    $collectionResponse = new MailFolderCollectionResponse;
    $collectionResponse->setValue([$parent]);

    $promise = new FulfilledPromise($collectionResponse);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('get')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->andReturn($userBuilder);

    // Mock for grandchild fetch
    $gcChildFoldersBuilder = Mockery::mock(ChildFoldersRequestBuilder::class);
    $gcChildFoldersBuilder->shouldReceive('get')->once()->andReturn($grandchildPromise);

    $gcFolderItemBuilder = Mockery::mock(MailFolderItemRequestBuilder::class);
    $gcFolderItemBuilder->shouldReceive('childFolders')->once()->andReturn($gcChildFoldersBuilder);

    $gcFoldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $gcFoldersBuilder->shouldReceive('byMailFolderId')->with('child-id')->once()->andReturn($gcFolderItemBuilder);

    $gcUserBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $gcUserBuilder->shouldReceive('mailFolders')->once()->andReturn($gcFoldersBuilder);

    $gcUsersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $gcUsersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($gcUserBuilder);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn(new FulfilledPromise(null));

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->andReturn($usersBuilder, $gcUsersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->tree('user@test.com');

    expect($result)->toHaveCount(1);
    expect($result[0]->getChildFolders())->toHaveCount(1);
    expect($result[0]->getChildFolders()[0]->getChildFolders())->toHaveCount(1);
    expect($result[0]->getChildFolders()[0]->getChildFolders()[0]->getId())->toBe('grandchild-id');
});

// ──────────────────────────────────────────────────────────────────────────────
// all() tests
// ──────────────────────────────────────────────────────────────────────────────

it('flattens all folders from the tree', function (): void {
    $child = new MailFolder;
    $child->setId('child-id');
    $child->setDisplayName('Child');
    $child->setChildFolderCount(0);
    $child->setChildFolders([]);

    $parent = new MailFolder;
    $parent->setId('parent-id');
    $parent->setDisplayName('Parent');
    $parent->setChildFolderCount(1);
    $parent->setChildFolders([$child]);

    $standalone = new MailFolder;
    $standalone->setId('standalone-id');
    $standalone->setDisplayName('Standalone');
    $standalone->setChildFolderCount(0);
    $standalone->setChildFolders([]);

    $collectionResponse = new MailFolderCollectionResponse;
    $collectionResponse->setValue([$parent, $standalone]);

    $promise = new FulfilledPromise($collectionResponse);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('get')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->once()->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($userBuilder);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn(new FulfilledPromise(null));

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->all('user@test.com');

    expect($result)->toBeInstanceOf(Collection::class);
    // parent + child + standalone = 3
    expect($result)->toHaveCount(3);
    expect($result[0]->getId())->toBe('parent-id');
    expect($result[1]->getId())->toBe('child-id');
    expect($result[2]->getId())->toBe('standalone-id');
});

it('returns empty collection when no folders exist', function (): void {
    $collectionResponse = new MailFolderCollectionResponse;
    $collectionResponse->setValue([]);

    $promise = new FulfilledPromise($collectionResponse);

    $foldersBuilder = Mockery::mock(MailFoldersRequestBuilder::class);
    $foldersBuilder->shouldReceive('get')->once()->andReturn($promise);

    $userBuilder = Mockery::mock(UserItemRequestBuilder::class);
    $userBuilder->shouldReceive('mailFolders')->once()->andReturn($foldersBuilder);

    $usersBuilder = Mockery::mock(UsersRequestBuilder::class);
    $usersBuilder->shouldReceive('byUserId')->with('user@test.com')->once()->andReturn($userBuilder);

    $requestAdapter = Mockery::mock(RequestAdapter::class);
    $requestAdapter->shouldReceive('sendAsync')->andReturn(new FulfilledPromise(null));

    $client = Mockery::mock(GraphServiceClient::class);
    $client->shouldReceive('users')->once()->andReturn($usersBuilder);
    $client->shouldReceive('getRequestAdapter')->andReturn($requestAdapter);

    $clientService = Mockery::mock(ClientService::class);
    $clientService->shouldReceive('client')->andReturn($client);

    $service = new FolderService($clientService);
    $result = $service->all('user@test.com');

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toBeEmpty();
});
