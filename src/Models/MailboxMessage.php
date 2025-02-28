<?php

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Casts\Body;
use Actengage\Mailbox\Casts\ExternalId;
use Actengage\Mailbox\Casts\FollowupFlag;
use Actengage\Mailbox\Casts\Importance;
use Actengage\Mailbox\Casts\Recipient;
use Actengage\Mailbox\Casts\Recipients;
use Database\Factories\MailboxMessageFactory;
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
    'folder_id' => 'number',
    'external_id' => 'string',
    'conversation_id' => 'string',
    'conversation_index' => 'string',
    'is_pinned' => 'boolean',
    'is_read' => 'boolean',
    'is_draft' => 'boolean',
    'flag' => 'FollowupFlag',
    'importance' => 'Importance',
    'from' => 'string',
    'to' => 'string[]',
    'cc' => 'string[]',
    'bcc' => 'string[]',
    'reply_to' => 'string[]',
    'subject' => 'string',
    'body' => 'string',
    'body_preview?' => 'string',
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
    use HasFactory;
    
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
        'received_at',
        'sent_at',
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
            'received_at' => 'timestamp',
            'sent_at' => 'timestamp',
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
     * Scope the query to the given external ids.
     *
     * @param Builder $query
     * @param string ...$id
     * @return void
     */
    public function scopeExternalId(Builder $query, string ...$id): void
    {
        $query->whereIn('external_id', $id);
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
     * Create a new factory.
     *
     * @return MailboxMessageFactory
     */
    protected static function newFactory(): MailboxMessageFactory
    {
        return MailboxMessageFactory::new();
    }

}
