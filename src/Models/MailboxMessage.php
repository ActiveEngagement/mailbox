<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Casts\Body;
use Actengage\Mailbox\Casts\ExternalId;
use Actengage\Mailbox\Casts\FollowupFlag;
use Actengage\Mailbox\Casts\Importance;
use Actengage\Mailbox\Casts\Recipient;
use Actengage\Mailbox\Casts\Recipients;
use Actengage\Mailbox\Data\EmailAddress;
use Actengage\Mailbox\Data\FollowupFlag as FollowupFlagData;
use Actengage\Mailbox\Enums\Importance as ImportanceEnum;
use Actengage\Mailbox\Events\MailboxMessageCreated;
use Actengage\Mailbox\Events\MailboxMessageDeleted;
use Actengage\Mailbox\Events\MailboxMessageDeleting;
use Actengage\Mailbox\Events\MailboxMessageUpdated;
use Actengage\Mailbox\Support\BroadcastsEventsToOthers;
use Database\Factories\MailboxMessageFactory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Override;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property int $mailbox_id
 * @property int|null $folder_id
 * @property string $external_id
 * @property string $hash
 * @property string|null $conversation_id
 * @property string|null $conversation_index
 * @property string|null $internet_message_id
 * @property string|null $in_reply_to
 * @property string|null $references
 * @property bool $is_pinned
 * @property bool $is_read
 * @property bool $is_draft
 * @property FollowupFlagData|null $flag
 * @property ImportanceEnum $importance
 * @property EmailAddress|null $from
 * @property Collection<int, EmailAddress>|null $to
 * @property Collection<int, EmailAddress>|null $cc
 * @property Collection<int, EmailAddress>|null $bcc
 * @property Collection<int, EmailAddress>|null $reply_to
 * @property string|null $subject
 * @property string|null $body
 * @property string|null $body_preview
 * @property string|null $body_message
 * @property Carbon|null $received_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Mailbox $mailbox
 * @property-read MailboxFolder|null $folder
 */
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
    use BroadcastsEventsToOthers;

    /** @use HasFactory<MailboxMessageFactory> */
    use HasFactory;

    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     * @var array<string, class-string>
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
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
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
     * @return BelongsTo<Mailbox, $this>
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * Get the parent folder.
     *
     * @return BelongsTo<MailboxFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MailboxFolder::class);
    }

    /**
     * Get the attachments.
     *
     * @return HasMany<MailboxMessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MailboxMessageAttachment::class, 'message_id');
    }

    /**
     * Scope the query to the given mailboxes.
     *
     * @param  Builder<MailboxMessage>  $query
     */
    protected function scopeMailbox(Builder $query, Mailbox|string ...$mailbox): void
    {
        $query->whereIn('mailbox_id', collect($mailbox)->map(fn (Mailbox|string $mailbox): mixed => $mailbox instanceof Mailbox ? $mailbox->getKey() : $mailbox));
    }

    /**
     * Scope the query to the given folders.
     *
     * @param  Builder<MailboxMessage>  $query
     */
    protected function scopeFolder(Builder $query, MailboxFolder|string ...$folder): void
    {
        $query->whereIn('folder_id', collect($folder)->map(fn (MailboxFolder|string $folder): mixed => $folder instanceof MailboxFolder ? $folder->getKey() : $folder));
    }

    /**
     * Scope the query to the given external ids.
     *
     * @param  Builder<MailboxMessage>  $query
     */
    #[Scope]
    protected function externalId(Builder $query, MailboxMessage|string ...$message): void
    {
        $query->whereIn('external_id', collect($message)->map(fn (MailboxMessage|string $message): string => $message instanceof MailboxMessage ? $message->external_id : $message));
    }

    /**
     * Scope the query to the given converstation ids.
     *
     * @param  Builder<MailboxMessage>  $query
     */
    #[Scope]
    protected function conversation(Builder $query, MailboxMessage|string ...$message): void
    {
        $query->whereIn('conversation_id', collect($message)->map(fn (MailboxMessage|string $message): string => $message instanceof MailboxMessage ? (string) $message->conversation_id : $message));
    }

    /**
     * Scope the query to the given message ids.
     *
     * @param  Builder<MailboxMessage>  $query
     */
    #[Scope]
    protected function message(Builder $query, MailboxMessage|string|int ...$message): void
    {
        $query->whereIn('id', collect($message)->map(fn (MailboxMessage|string|int $message): mixed => $message instanceof MailboxMessage ? $message->getKey() : $message));
    }

    /**
     * Get the attachment relative storage path.
     */
    public function attachmentRelativePath(?string $filename = null): string
    {
        return rtrim(sprintf('%s/%s/%s', $this->mailbox->email, $this->hash, $filename), '/');
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, Channel|Model>
     */
    public function broadcastOn(string $event): array
    {
        return array_values(array_filter([$this, $this->folder, $this->mailbox]));
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'from' => $this->from,
            'subject' => $this->subject,
        ];
    }

    /**
     * Create a new factory.
     */
    protected static function newFactory(): MailboxMessageFactory
    {
        return MailboxMessageFactory::new();
    }
}
