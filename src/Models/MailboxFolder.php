<?php

namespace Actengage\Mailbox\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Microsoft\Graph\Generated\Models\MailFolder;
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
class MailboxFolder extends Model
{
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
     * Scope the query using the MailFolder instance.
     *
     * @param Builder $query
     * @param MailFolder|string ...$id
     * @return void
     */
    public function scopeFolder(Builder $query, MailFolder|string ...$id): void
    {
        $query->whereIn('external_id', collect($id)->map(function(MailFolder|string $id) {
            return $id instanceof MailFolder ? $id->getId() : $id;
        }));
    }

    /**
     * Scope the query to the parent id.
     *
     * @param Builder $query
     * @param MailFolder|string ...$id
     * @return void
     */
    public function scopeParent(Builder $query, MailFolder|string ...$id): void
    {
        $query->whereIn('external_id', collect($id)->map(function(MailFolder|string $id) {
            return $id instanceof MailFolder ? $id->getParentFolderId() : $id;
        }));
    }
}
