<?php

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Events\MailboxCreated;
use Actengage\Mailbox\Events\MailboxDeleted;
use Actengage\Mailbox\Events\MailboxUpdated;
use Database\Factories\MailboxFactory;
use Illuminate\Database\Eloquent\BroadcastsEvents;
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
    use BroadcastsEvents, HasFactory;

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
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string,class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxCreated::class,
        'updated' => MailboxUpdated::class,
        'deleted' => MailboxDeleted::class,
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
     * Get the Archive folder.
     *
     * @return MailboxFolder|null
     */
    public function archiveFolder(): ?MailboxFolder
    {
        return Cache::rememberForever("mailbox.{$this->id}.folders.archive", function() {
            return $this->folders()->whereName('Archive')->first();
        });
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
     * Get the Deleted Items folder.
     *
     * @return MailboxFolder|null
     */
    public function deletedItemsFolder(): ?MailboxFolder
    {
        return Cache::rememberForever("mailbox.{$this->id}.folders.deletedItems", function() {
            return $this->folders()->whereName('Deleted Items')->first();
        });
    }

    /**
     * Get the Sent Items folder.
     *
     * @return MailboxFolder|null
     */
    public function sentItemsFolder(): ?MailboxFolder
    {
        return Cache::rememberForever("mailbox.{$this->id}.folders.sentItems", function() {
            return $this->folders()->whereName('Sent Items')->first();
        });
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel|\Illuminate\Database\Eloquent\Model>
     */
    public function broadcastOn(string $event): array
    {
        return [$this];
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
