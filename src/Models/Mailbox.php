<?php

namespace Actengage\Mailbox\Models;

use Database\Factories\MailboxFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[LiteralTypeScriptType([
    'id' => 'number',
    'email' => 'string',
    'connection' => 'string',
    'created_at' => 'string',
    'updated_at' => 'string',
    'folders?' => 'MailboxFolder[]',
    'messages?' => 'MailboxMessage[]',
    'subscriptions?' => 'MailboxSubscription[]'
])]
class Mailbox extends Model
{    
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'connection'
    ];

    /**
     * Get the folders assigned to the mailbox.
     *
     * @return HasMany<MailboxFolder>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(MailboxFolder::class);
    }

    /**
     * Get the messages assigned to the mailbox.
     *
     * @return HasMany<MailboxMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MailboxMessage::class, 'mailbox_id');
    }

    /**
     * Get the subscriptions assigned to the mailbox.
     *
     * @return HasMany<MailboxSubscription>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MailboxSubscription::class);
    }
    
    /**
     * Scope the query for the give email addresses.
     *
     * @param Builder $query
     * @param Mailbox|string ...$email
     * @return void
     */
    public function scopeEmail(Builder $query, Mailbox|string ...$email): void
    {
        $query->whereIn('email', collect($email)->map(function(Mailbox|string $mailbox) {
            return $mailbox instanceof Mailbox ? $mailbox->email : $mailbox;
        }));
    }

    /**
     * Scope the query to the given mailboxes.
     *
     * @param Builder $query
     * @param Mailbox|string ...$mailbox
     * @return void
     */
    public function scopeMailbox(Builder $query, Mailbox|string ...$mailbox): void
    {
        $query->whereIn('id', collect($mailbox)->map(function(Mailbox|string $mailbox) {
            return $mailbox instanceof Mailbox ? $mailbox->getKey() : $mailbox;
        }));
    }

    /**
     * Get the Drafts folder.
     *
     * @return MailboxFolder|null
     */
    public function draftsFolder(): ?MailboxFolder
    {
        return Cache::rememberForever("mailbox.{$this->id}.folders.drafts", function() {
            return $this->folders()->whereName('Drafts')->first();
        });
    }

    /**
     * Create a new factory.
     *
     * @return MailboxFactory
     */
    protected static function newFactory(): MailboxFactory
    {
        return MailboxFactory::new();
    }
}
