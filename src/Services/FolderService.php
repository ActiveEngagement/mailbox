<?php

namespace Actengage\Mailbox\Services;

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Illuminate\Support\Collection;
use Microsoft\Graph\Core\Tasks\PageIterator;
use Microsoft\Graph\Generated\Models\MailFolder;
use Microsoft\Graph\Generated\Models\MailFolderCollectionResponse;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\ChildFolders\ChildFoldersRequestBuilderGetQueryParameters;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\ChildFolders\ChildFoldersRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\MailFolderItemRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\MailFolderItemRequestBuilderGetQueryParameters;
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
     * @param string $userId
     * @param string $folderId
     * @return MailFolder
     * @throws \Exception
     */
    public function find(Mailbox|string $mailbox, string $folderId): MailFolder
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
            ->get($config)
            ->wait();
    }

    /**
     * Get a all the folders as a flattened collection for the given user.
     *
     * @param string $userId
     * @return Collection
     * @throws \Exception
     */
    public function all(string $userId): Collection
    {
        $reduce = function(Collection $collection) use (&$reduce) {
            return $collection->reduce(function(Collection $carry, MailFolder $folder) use (&$reduce) {
                $carry->push($folder);

                if($folder->getChildFolderCount()) {
                    $carry->push(...$reduce(collect($folder->getChildFolders())));
                }

                return $carry;
            }, collect());
        };

        return $reduce(collect($this->tree($userId)));
    }

    /**
     * Get all tree of folders for the given user.
     *
     * @param string $userId
     * @return Collection<int,MailFolder>
     * @throws \Exception
     */
    public function tree(string $userId): Collection
    {
        $config = new MailFoldersRequestBuilderGetRequestConfiguration(
            queryParameters: new MailFoldersRequestBuilderGetQueryParameters(
                top: 100,
                expand: ['childFolders']
            )
        );
        
        $response = $this->service->client()->users()
            ->byUserId($userId)
            ->mailFolders()
            ->get($config)
            ->wait();

        $folders = collect();

        $pageIterator = new PageIterator(
            $response, $this->service->client()->getRequestAdapter()
        );

        while($pageIterator->hasNext()) {
            $pageIterator->iterate(function(MailFolder $folder) use ($userId, $folders) {
                if($folder->getChildFolderCount() && !count($folder->getChildFolders())) {
                    $folder->setChildFolders(
                        $this->getChildFolders($userId, $folder->getId())->getValue()
                    );
                }
                else if($folder->getChildFolderCount()) {
                    foreach($folder->getChildFolders() as $child) {
                        if($child->getChildFolderCount()) {
                            $child->setChildFolders(
                                $this->getChildFolders($userId, $child->getId())->getValue()
                            );
                        }
                    }
                }

                $folders->push($folder);
            });
        }
            
        return $folders;
    }

    /**
     * Get the child folders for a given user and folder id.
     *
     * @param string $userId
     * @param string $folderId
     * @return MailFolderCollectionResponse
     */
    public function getChildFolders(string $userId, string $folderId): MailFolderCollectionResponse
    {
        $config = new ChildFoldersRequestBuilderGetRequestConfiguration(
            queryParameters: new ChildFoldersRequestBuilderGetQueryParameters(
                expand: ['childFolders']
            )
        );
        
        $response = $this->service->client()->users()
            ->byUserId($userId)
            ->mailFolders()
            ->byMailFolderId($folderId)
            ->childFolders()
            ->get($config)
            ->wait();

        foreach($response->getValue() as $folder) {
            if($folder->getChildFolderCount()) {
                $folder->setChildFolders(
                    $this->getChildFolders($userId, $folder->getId())->getValue()
                );
            }
        }

        return $response;
    }

    /**
     * Save the folder to give the mailbox.
     *
     * @param Mailbox $mailbox
     * @param MailFolder $folder
     * @return MailboxFolder
     */
    public function save(Mailbox $mailbox, MailFolder $folder): MailboxFolder
    {
        $model = $mailbox->folders()->firstOrNew([
            'external_id' => $folder->getId()
        ]);

        $model->fill([
            'name' => $folder->getDisplayName(),
            'is_hidden' => $folder->getIsHidden(),
        ]);

        if($folder->getParentFolderId()) {
            $model->parent()->associate(
                MailboxFolder::query()->externalId($folder->getParentFolderId())->first()
            );
        }

        foreach(collect($folder->getChildFolders()) as $child) {
            $this->save($mailbox, $child);
        }

        $model->save();

        return $model;
    }
}