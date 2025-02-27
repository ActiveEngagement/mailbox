<?php

namespace Actengage\Mailbox\Models;

use Database\Factories\MailboxMessageAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
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
