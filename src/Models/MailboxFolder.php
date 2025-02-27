<?php

namespace Actengage\Mailbox\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Microsoft\Graph\Generated\Models\MailFolder;

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
