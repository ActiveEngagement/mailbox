<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Events\MailboxFolderCreated;
use Actengage\Mailbox\Events\MailboxFolderDeleted;
use Actengage\Mailbox\Events\MailboxFolderDeleting;
use Actengage\Mailbox\Events\MailboxFolderUpdated;
use Actengage\Mailbox\Observers\MailboxFolderObserver;
use Actengage\Mailbox\Support\BroadcastsEventsToOthers;
use Database\Factories\MailboxFolderFactory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kalnoy\Nestedset\NodeTrait;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property int $mailbox_id
 * @property int|null $parent_id
 * @property string $external_id
 * @property string $name
 * @property bool $is_hidden
 * @property bool $is_favorite
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Mailbox|null $mailbox
 */
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
    'children?' => 'MailboxFolder[]',
    'messages?' => 'MailboxMessage[]',
])]
#[ObservedBy(MailboxFolderObserver::class)]
class MailboxFolder extends Model
{
    use BroadcastsEventsToOthers;

    /** @use HasFactory<MailboxFolderFactory> */
    use HasFactory;

    use NodeTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'external_id',
        'name',
        'is_hidden',
        'is_favorite',
    ];

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxFolderCreated::class,
        'updated' => MailboxFolderUpdated::class,
        'deleting' => MailboxFolderDeleting::class,
        'deleted' => MailboxFolderDeleted::class,
    ];

    /**
     * Get the parent mailbox.
     *
     * @return BelongsTo<Mailbox, $this>
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Get the parent folder.
     *
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the messages that belong to folder.
     *
     * @return HasMany<MailboxMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MailboxMessage::class, 'folder_id');
    }

    /**
     * Scope the query to the given mailbox.
     *
     * @param  Builder<MailboxFolder>  $query
     */
    protected function scopeMailbox(Builder $query, Mailbox ...$mailbox): void
    {
        $query->whereIn('mailbox_id', $mailbox);
    }

    /**
     * Scope the query to the given folders.
     *
     * @param  Builder<MailboxFolder>  $query
     */
    #[Scope]
    protected function folder(Builder $query, MailboxFolder|string ...$folder): void
    {
        $query->whereIn('id', collect($folder)->map(fn (MailboxFolder|string $folder): mixed => $folder instanceof MailboxFolder ? $folder->getKey() : $folder));
    }

    /**
     * Scope the query to the given parent folders.
     *
     * @param  Builder<MailboxFolder>  $query
     */
    protected function scopeParent(Builder $query, MailboxFolder|string ...$folder): void
    {
        $query->whereIn('parent_id', collect($folder)->map(fn (MailboxFolder|string $folder): mixed => $folder instanceof MailboxFolder ? $folder->getKey() : $folder));
    }

    /**
     * Scope the query to the given external id.
     *
     * @param  Builder<MailboxFolder>  $query
     */
    #[Scope]
    protected function externalId(Builder $query, MailboxFolder|string ...$folder): void
    {
        $query->whereIn('external_id', collect($folder)->map(fn (MailboxFolder|string $folder): string => $folder instanceof MailboxFolder ? $folder->external_id : $folder));
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, Channel|Model>
     */
    public function broadcastOn(string $event): array
    {
        return array_filter([$this, $this->mailbox]);
    }

    /**
     * Create a new factory.
     */
    protected static function newFactory(): MailboxFolderFactory
    {
        return MailboxFolderFactory::new();
    }
}
