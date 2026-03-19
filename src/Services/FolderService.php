<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Exception;
use Http\Promise\Promise;
use Illuminate\Support\Collection;
use Microsoft\Graph\Core\Tasks\PageIterator;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Models\MailFolderCollectionResponse;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\ChildFolders\ChildFoldersRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\ChildFolders\ChildFoldersRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\MailFolderItemRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\MailFolderItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\MailFoldersRequestBuilderGetRequestConfiguration;

class FolderService
{
    public function __construct(
        protected ClientService $service
    ) {
        //
    }

    /**
     * Find a folder by the given user and folder id.
     *
     * @return Promise<MailFolder|null>
     */
    public function find(Mailbox|string $mailbox, string $folderId): Promise
    {
        $config = new MailFolderItemRequestBuilderGetRequestConfiguration(
            queryParameters: new MailFolderItemRequestBuilderGetQueryParameters(
                expand: ['childFolders']
            )
        );

        return $this->service->client()->users()
            ->byUserId($mailbox instanceof Mailbox ? $mailbox->email : $mailbox)
            ->mailFolders()
            ->byMailFolderId($folderId)
            ->get($config);
    }

    /**
     * Get a all the folders as a flattened collection for the given user.
     *
     * @return Collection<int, MailFolder>
     *
     * @throws Exception
     */
    public function all(string $userId): Collection
    {
        /** @var Collection<int, MailFolder> $result */
        $result = collect();

        $this->flattenFolders($this->tree($userId), $result);

        return $result;
    }

    /**
     * Recursively flatten folders into a single collection.
     *
     * @param  Collection<int, MailFolder>  $folders
     * @param  Collection<int, MailFolder>  $result
     */
    private function flattenFolders(Collection $folders, Collection $result): void
    {
        foreach ($folders as $folder) {
            $result->push($folder);

            if ($folder->getChildFolderCount()) {
                $this->flattenFolders(collect($folder->getChildFolders()), $result);
            }
        }
    }

    /**
     * Get all tree of folders for the given user.
     *
     * @return Collection<int, MailFolder>
     *
     * @throws Exception
     */
    public function tree(string $userId): Collection
    {
        $config = new MailFoldersRequestBuilderGetRequestConfiguration(
            queryParameters: new MailFoldersRequestBuilderGetQueryParameters(
                expand: ['childFolders'],
                top: 100
            )
        );

        /** @var MailFolderCollectionResponse $response */
        $response = $this->service->client()->users()
            ->byUserId($userId)
            ->mailFolders()
            ->get($config)
            ->wait();

        /** @var Collection<int, MailFolder> $folders */
        $folders = collect();

        /** @var PageIterator<MailFolder> $pageIterator */
        $pageIterator = new PageIterator(
            $response, $this->service->client()->getRequestAdapter()
        );

        while ($pageIterator->hasNext()) {
            $pageIterator->iterate(
                /** @param array<mixed>|object $item */
                function (array|object $item) use ($userId, $folders): bool {
                    if (! $item instanceof MailFolder) {
                        return true; // @codeCoverageIgnore
                    }

                    $folder = $item;
                    if ($folder->getChildFolderCount() && (array) $folder->getChildFolders() === []) {
                        $folder->setChildFolders(
                            $this->getChildFolders($userId, (string) $folder->getId())->getValue()
                        );
                    } elseif ($folder->getChildFolderCount()) {
                        foreach ((array) $folder->getChildFolders() as $child) {
                            if ($child->getChildFolderCount()) {
                                $child->setChildFolders(
                                    $this->getChildFolders($userId, (string) $child->getId())->getValue()
                                );
                            }
                        }
                    }

                    $folders->push($folder);

                    return true;
                }
            );
        }

        return $folders;
    }

    /**
     * Get the child folders for a given user and folder id.
     */
    public function getChildFolders(string $userId, string $folderId): MailFolderCollectionResponse
    {
        $config = new ChildFoldersRequestBuilderGetRequestConfiguration(
            queryParameters: new ChildFoldersRequestBuilderGetQueryParameters(
                expand: ['childFolders']
            )
        );

        /** @var MailFolderCollectionResponse $response */
        $response = $this->service->client()->users()
            ->byUserId($userId)
            ->mailFolders()
            ->byMailFolderId($folderId)
            ->childFolders()
            ->get($config)
            ->wait();

        foreach ((array) $response->getValue() as $folder) {
            if ($folder->getChildFolderCount()) {
                $folder->setChildFolders(
                    $this->getChildFolders($userId, (string) $folder->getId())->getValue()
                );
            }
        }

        return $response;
    }

    /**
     * Save the folder to give the mailbox.
     */
    public function save(Mailbox $mailbox, MailFolder $folder): MailboxFolder
    {
        $model = $mailbox->folders()->firstOrNew([
            'external_id' => $folder->getId(),
        ]);

        $model->fill([
            'name' => $folder->getDisplayName(),
            'is_hidden' => $folder->getIsHidden(),
        ]);

        if ($folder->getParentFolderId()) {
            $model->parent()->associate(
                MailboxFolder::query()->externalId($folder->getParentFolderId())->first()
            );
        }

        foreach ((array) $folder->getChildFolders() as $child) {
            $this->save($mailbox, $child);
        }

        $model->save();

        return $model;
    }
}
