<?php

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Events\MailboxFolderCreated;
use Actengage\Mailbox\Events\MailboxFolderDeleted;
use Actengage\Mailbox\Events\MailboxFolderUpdated;
use Actengage\Mailbox\Observers\MailboxFolderObserver;
use Actengage\Mailbox\Support\BroadcastsEventsToOthers;
use Database\Factories\MailboxFolderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[LiteralTypeScriptType([
    'id' => 'number',
    'mailbox_id' => 'number',
    'parent_id?' => 'number',
    'external_id' => 'string',
    'name' => 'string',
    'is_hidden' => 'boolean',
    'is_favorite' => 'boolean',
    'created_at' => 'string',
    'updated_at' => 'string',
    'mailbox?' => 'Mailbox',
    'parent?' => 'MailboxFolder',
    'messages?' => 'MailboxMessage[]',
])]
#[ObservedBy(MailboxFolderObserver::class)]
class MailboxFolder extends Model
{
    use BroadcastsEventsToOthers, HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_id',
        'name',
        'is_hidden',
        'is_favorite'
    ];

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string,class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxFolderCreated::class,
        'updated' => MailboxFolderUpdated::class,
        'deleted' => MailboxFolderDeleted::class,
    ];

    /**
     * Get the parent mailbox.
     *
     * @return BelongsTo<Mailbox>
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Get the parent folder.
     *
     * @return BelongsTo<MailboxFolder>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Get the messages that belong to folder.
     *
     * @return HasMany<MailboxMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MailboxMessage::class, 'folder_id');
    }

    /**
     * Scope the query to the given folders.
     *
     * @param Builder $query
     * @param MailboxFolder|string ...$folder
     * @return void
     */
    public function scopeFolder(Builder $query, MailboxFolder|string ...$folder): void
    {
        $query->whereIn('id', collect($folder)->map(function(MailboxFolder|string $folder) {
            return $folder instanceof MailboxFolder ? $folder->getKey() : $folder;
        }));
    }

    /**
     * Scope the query to the given parent folders.
     *
     * @param Builder $query
     * @param MailboxFolder|string ...$folder
     * @return void
     */
    public function scopeParent(Builder $query, MailboxFolder|string ...$folder): void
    {
        $query->whereIn('parent_id', collect($folder)->map(function(MailboxFolder|string $folder) {
            return $folder instanceof MailboxFolder ? $folder->getKey() : $folder;
        }));
    }

    /**
     * Scope the query to the given external id.
     *
     * @param Builder $query
     * @param MailboxFolder|string ...$externalId
     * @return void
     */
    public function scopeExternalId(Builder $query, MailboxFolder|string ...$folder): void
    {
        $query->whereIn('external_id', collect($folder)->map(function(MailboxFolder|string $folder) {
            return $folder instanceof MailboxFolder ? $folder->external_id : $folder;
        }));
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Database\Eloquent\Model>
     */
    public function broadcastOn(string $event): array
    {
        return [$this, $this->mailbox];
    }

    /**
     * Create a new factory.
     *
     * @return MailboxFolderFactory
     */
    protected static function newFactory(): MailboxFolderFactory
    {
        return MailboxFolderFactory::new();
    }
}
