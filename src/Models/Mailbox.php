<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Events\MailboxCreated;
use Actengage\Mailbox\Events\MailboxDeleted;
use Actengage\Mailbox\Events\MailboxDeleting;
use Actengage\Mailbox\Events\MailboxUpdated;
use Actengage\Mailbox\Support\BroadcastsEventsToOthers;
use Database\Factories\MailboxFactory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property string $email
 * @property string $connection
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[TypeScript]
#[LiteralTypeScriptType([
    'id' => 'number',
    'email' => 'string',
    'connection' => 'string',
    'created_at' => 'string',
    'updated_at' => 'string',
    'folders?' => 'MailboxFolder[]',
    'messages?' => 'MailboxMessage[]',
    'subscriptions?' => 'MailboxSubscription[]',
])]
class Mailbox extends Model
{
    use BroadcastsEventsToOthers;

    /** @use HasFactory<MailboxFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'connection',
    ];

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxCreated::class,
        'updated' => MailboxUpdated::class,
        'deleting' => MailboxDeleting::class,
        'deleted' => MailboxDeleted::class,
    ];

    /**
     * Get the folders assigned to the mailbox.
     *
     * @return HasMany<MailboxFolder, $this>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(MailboxFolder::class);
    }

    /**
     * Get the messages assigned to the mailbox.
     *
     * @return HasMany<MailboxMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MailboxMessage::class, 'mailbox_id');
    }

    /**
     * Get the subscriptions assigned to the mailbox.
     *
     * @return HasMany<MailboxSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MailboxSubscription::class);
    }

    /**
     * Scope the query for the give email addresses.
     *
     * @param  Builder<Mailbox>  $query
     */
    protected function scopeEmail(Builder $query, Mailbox|string ...$email): void
    {
        $query->whereIn('email', collect($email)->map(fn (Mailbox|string $mailbox): string => $mailbox instanceof Mailbox ? $mailbox->email : $mailbox));
    }

    /**
     * Scope the query to the given mailboxes.
     *
     * @param  Builder<Mailbox>  $query
     */
    protected function scopeMailbox(Builder $query, Mailbox|string ...$mailbox): void
    {
        $query->whereIn('id', collect($mailbox)->map(fn (Mailbox|string $mailbox): mixed => $mailbox instanceof Mailbox ? $mailbox->getKey() : $mailbox));
    }

    /**
     * Get the Archive folder.
     */
    public function archiveFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.archive', $this->id), fn () => $this->folders()->whereName('Archive')->first());
    }

    /**
     * Get the Conversation History folder.
     */
    public function conversationHistoryFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.conversationHistory', $this->id), fn () => $this->folders()->whereName('Conversation History')->first());
    }

    /**
     * Get the Deleted Items folder.
     */
    public function deletedItemsFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.deletedItems', $this->id), fn () => $this->folders()->whereName('Deleted Items')->first());
    }

    /**
     * Get the Drafts folder.
     */
    public function draftsFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.drafts', $this->id), fn () => $this->folders()->whereName('Drafts')->first());
    }

    /**
     * Get the Inbox folder.
     */
    public function inboxFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.inbox', $this->id), fn () => $this->folders()->whereName('Inbox')->first());
    }

    /**
     * Get the Junk Email folder.
     */
    public function junkEmailFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.junkEmail', $this->id), fn () => $this->folders()->whereName('Junk Email')->first());
    }

    /**
     * Get the Outbox folder.
     */
    public function outboxFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.outbox', $this->id), fn () => $this->folders()->whereName('Outbox')->first());
    }

    /**
     * Get the Sent Items folder.
     */
    public function sentItemsFolder(): ?MailboxFolder
    {
        return Cache::rememberForever(sprintf('mailbox.%s.folders.sentItems', $this->id), fn () => $this->folders()->whereName('Sent Items')->first());
    }

    /**
     * Get the channels that model events should broadcast on.
     *
     * @return array<int, Channel|Model>
     */
    public function broadcastOn(string $event): array
    {
        return [$this];
    }

    /**
     * Create a new factory.
     */
    protected static function newFactory(): MailboxFactory
    {
        return MailboxFactory::new();
    }
}
