<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Events\MailboxMessageAttachmentCreated;
use Actengage\Mailbox\Events\MailboxMessageAttachmentDeleted;
use Actengage\Mailbox\Events\MailboxMessageAttachmentDeleting;
use Actengage\Mailbox\Events\MailboxMessageAttachmentUpdated;
use Actengage\Mailbox\Support\BroadcastsEventsToOthers;
use Database\Factories\MailboxMessageAttachmentFactory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;
use Override;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property int $mailbox_id
 * @property int $message_id
 * @property string $disk
 * @property string $name
 * @property int $size
 * @property string $content_type
 * @property string $path
 * @property string $url
 * @property string|null $contents
 * @property string $base64_contents
 * @property Carbon|null $last_modified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Mailbox|null $mailbox
 * @property-read MailboxMessage|null $message
 */
#[TypeScript]
#[LiteralTypeScriptType([
    'id' => 'number',
    'mailbox_id' => 'number',
    'name' => 'string',
    'size' => 'number',
    'content_type' => 'string',
    'disk' => 'string',
    'path' => 'string',
    'url' => 'string',
    'last_modified_at' => 'string',
    'created_at' => 'string',
    'updated_at' => 'string',
    'mailbox?' => 'Mailbox',
    'message?' => 'MailboxMessage',
])]
class MailboxMessageAttachment extends Model
{
    use BroadcastsEventsToOthers;

    /** @use HasFactory<MailboxMessageAttachmentFactory> */
    use HasFactory;

    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'disk',
        'name',
        'size',
        'content_type',
        'path',
        'last_modified_at',
    ];

    /**
     * The attributes that are appended.
     *
     * @var list<string>
     */
    protected $appends = [
        'url',
    ];

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxMessageAttachmentCreated::class,
        'updated' => MailboxMessageAttachmentUpdated::class,
        'deleting' => MailboxMessageAttachmentDeleting::class,
        'deleted' => MailboxMessageAttachmentDeleted::class,
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
            'last_modified_at' => 'datetime',
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
     * Get the parent message.
     *
     * @return BelongsTo<MailboxMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(MailboxMessage::class);
    }

    /**
     * Scope the query by the given name.
     *
     * @param  Builder<MailboxMessageAttachment>  $query
     */
    #[Scope]
    protected function named(Builder $query, string ...$name): void
    {
        $query->whereIn('name', $name);
    }

    /**
     * Get the absolute url from storage.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Storage::disk($this->disk)->url($this->path)
        );
    }

    /**
     * Get the contents from storage.
     *
     * @return Attribute<string|null, never>
     */
    protected function contents(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => Storage::disk($this->disk)->get($this->path)
        );
    }

    /**
     * Get the base64 contents from storage.
     *
     * @return Attribute<string, never>
     */
    protected function base64Contents(): Attribute
    {
        return Attribute::make(
            get: fn (): string => base64_encode((string) $this->contents)
        );
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, Channel|Model>
     */
    public function broadcastOn(string $event): array
    {
        return array_values(array_filter([$this, $this->mailbox, $this->message]));
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    /**
     * Create a new factory.
     */
    protected static function newFactory(): MailboxMessageAttachmentFactory
    {
        return MailboxMessageAttachmentFactory::new();
    }
}
