<?php

namespace Actengage\Mailbox\Models;

use Database\Factories\MailboxMessageAttachmentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

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
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'disk',
        'name',
        'size',
        'content_type',
        'path',
        'last_modified_at'
    ];

    /**
     * The attributes that are appended.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'url'
    ];

    /**
     * The attributes that are cast.
     *
     * @return array
     */
    public function casts(): array
    {
        return [
            'last_modified_at' => 'timestamp',
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
     * Get the parent mailbox.
     *
     * @return BelongsTo<MailboxMessage>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(MailboxMessage::class);
    }

    /**
     * Get the absolute url from storage.
     *
     * @return string
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: fn() => Storage::disk($this->disk)->url($this->path)
        );
    }

    /**
     * Create a new factory.
     *
     * @return MailboxMessageFactory
     */
    protected static function newFactory(): MailboxMessageAttachmentFactory
    {
        return MailboxMessageAttachmentFactory::new();
    }
}
