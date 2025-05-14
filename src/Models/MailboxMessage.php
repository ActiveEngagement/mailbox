<?php

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Casts\Body;
use Actengage\Mailbox\Casts\ExternalId;
use Actengage\Mailbox\Casts\FollowupFlag;
use Actengage\Mailbox\Casts\Importance;
use Actengage\Mailbox\Casts\Recipient;
use Actengage\Mailbox\Casts\Recipients;
use Actengage\Mailbox\Events\MailboxMessageCreated;
use Actengage\Mailbox\Events\MailboxMessageDeleted;
use Actengage\Mailbox\Events\MailboxMessageDeleting;
use Actengage\Mailbox\Events\MailboxMessageUpdated;
use Actengage\Mailbox\Support\BroadcastsEventsToOthers;
use Database\Factories\MailboxMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[LiteralTypeScriptType([
    'id' => 'number',
    'mailbox_id' => 'number',
    'folder_id' => 'number',
    'external_id' => 'string',
    'conversation_id' => 'string',
    'conversation_index' => 'string',
    'internet_message_id?' => 'string',
    'in_reply_to?' => 'string',
    'references?' => 'string',
    'is_pinned' => 'boolean',
    'is_read' => 'boolean',
    'is_draft' => 'boolean',
    'flag' => 'FollowupFlag',
    'importance' => 'Importance',
    'from?' => 'EmailAddress',
    'to' => 'EmailAddress[]',
    'cc' => 'EmailAddress[]',
    'bcc' => 'EmailAddress[]',
    'reply_to' => 'EmailAddress[]',
    'subject' => 'string',
    'body' => 'string',
    'body_preview?' => 'string',
    'body_message?' => 'string',
    'received_at' => 'string',
    'sent_at?' => 'string',
    'created_at' => 'string',
    'updated_at' => 'string',
    'mailbox?' => 'Mailbox',
    'folder?' => 'MailboxFolder',
    'attachments?' => 'MailboxMessageAttachment[]',
])]
class MailboxMessage extends Model
{
    use BroadcastsEventsToOthers, HasFactory, Searchable;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_id',
        'hash',
        'conversation_id',
        'conversation_index',
        'internet_message_id',
        'in_reply_to',
        'references',
        'is_pinned',
        'is_read',
        'is_draft',
        'flag',
        'importance',
        'from',
        'to',
        'cc',
        'bcc',
        'reply_to',
        'subject',
        'body',
        'body_preview',
        'body_message',
        'received_at',
        'sent_at',
    ];

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string,class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxMessageCreated::class,
        'updated' => MailboxMessageUpdated::class,
        'deleting' => MailboxMessageDeleting::class,
        'deleted' => MailboxMessageDeleted::class,
    ];

    /**
     * The attributes that are cast.
     *
     * @return array
     */
    public function casts(): array
    {
        return [
            'external_id' => ExternalId::class,
            'from' => Recipient::class,
            'to' => Recipients::class,
            'cc' => Recipients::class,
            'bcc' => Recipients::class,
            'reply_to' => Recipients::class,
            'body' => Body::class,
            'flag' => FollowupFlag::class,
            'importance' => Importance::class,
            'is_read' => 'boolean',
            'is_draft' => 'boolean',
            'received_at' => 'datetime:c',
            'due_at' => 'datetime:c',
            'started_at' => 'datetime:c',
            'completed_at' => 'datetime:c',
            'sent_at' => 'datetime:c',
        ];
    }

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
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MailboxFolder::class);
    }

    /**
     * Get the parent folder.
     *
     * @return HasMany<MailboxMessageAttachment>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MailboxMessageAttachment::class, 'message_id');
    }

    /**
     * Scope the query to the given mailboxes.
     *
     * @param Builder $query
     * @param MailboxFolder|string ...$folder
     * @return void
     */
    public function scopeMailbox(Builder $query, Mailbox|string ...$mailbox): void
    {
        $query->whereIn('mailbox_id', collect($mailbox)->map(function(Mailbox|string $mailbox) {
            return $mailbox instanceof Mailbox ? $mailbox->getKey() : $mailbox;
        }));
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
        $query->whereIn('folder_id', collect($folder)->map(function(MailboxFolder|string $folder) {
            return $folder instanceof MailboxFolder ? $folder->getKey() : $folder;
        }));
    }

    /**
     * Scope the query to the given external ids.
     *
     * @param Builder $query
     * @param MailboxMessage|string ...$message
     * @return void
     */
    public function scopeExternalId(Builder $query, MailboxMessage|string ...$message): void
    {
        $query->whereIn('external_id', collect($message)->map(function(MailboxMessage|string $message) {
            return $message instanceof MailboxMessage ? $message->external_id : $message;
        }));
    }

    /**
     * Scope the query to the given converstation ids.
     *
     * @param Builder $query
     * @param MailboxMessage|string ...$message
     * @return void
     */
    public function scopeConversation(Builder $query, MailboxMessage|string ...$message): void
    {
        $query->whereIn('conversation_id', collect($message)->map(function(MailboxMessage|string $message) {
            return $message instanceof MailboxMessage ? $message->conversation_id : $message;
        }));
    }

    /**
     * Scope the query to the given message ids.
     *
     * @param Builder $query
     * @param MailboxMessage|string|int ...$message
     * @return void
     */
    public function scopeMessage(Builder $query, MailboxMessage|string|int ...$message): void
    {
        $query->whereIn('id', collect($message)->map(function(MailboxMessage|string $message) {
            return $message instanceof MailboxMessage ? $message->getKey() : $message;
        }));
    }

    /**
     * Get the attachment relative storage path.
     *
     *
     * @param string|null $filename
     * @return string
     */
    public function attachmentRelativePath(?string $filename = null): string
    {
        return rtrim(sprintf('%s/%s/%s', $this->mailbox->email, $this->hash, $filename), '/');
    }
 
    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Database\Eloquent\Model>
     */
    public function broadcastOn(string $event): array
    {
        return [$this, $this->folder, $this->mailbox];
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        return [
            'from' => $this->from,
            'subject' => $this->subject
        ];
    }

    /**
     * Create a new factory.
     *
     * @return MailboxMessageFactory
     */
    protected static function newFactory(): MailboxMessageFactory
    {
        return MailboxMessageFactory::new();
    }

}
