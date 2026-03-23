<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Events\MailboxSubscriptionCreated;
use Actengage\Mailbox\Events\MailboxSubscriptionDeleted;
use Actengage\Mailbox\Events\MailboxSubscriptionDeleting;
use Actengage\Mailbox\Events\MailboxSubscriptionUpdated;
use Actengage\Mailbox\Observers\MailboxSubscriptionObserver;
use Actengage\Mailbox\Support\BroadcastsEventsToOthers;
use DateTime;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @property int $id
 * @property int $mailbox_id
 * @property string $external_id
 * @property string $resource
 * @property string $change_type
 * @property string $notification_url
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Mailbox|null $mailbox
 */
#[TypeScript]
#[LiteralTypeScriptType([
    'id' => 'number',
    'mailbox_id' => 'number',
    'external_id' => 'string',
    'resource' => 'string',
    'change_type' => 'string',
    'notification_url' => 'string',
    'expires_at' => 'string',
    'created_at' => 'string',
    'updated_at' => 'string',
    'mailbox?' => 'Mailbox',
])]
#[ObservedBy(MailboxSubscriptionObserver::class)]
class MailboxSubscription extends Model
{
    use BroadcastsEventsToOthers;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'external_id',
        'resource',
        'change_type',
        'notification_url',
        'expires_at',
    ];

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxSubscriptionCreated::class,
        'updated' => MailboxSubscriptionUpdated::class,
        'deleting' => MailboxSubscriptionDeleting::class,
        'deleted' => MailboxSubscriptionDeleted::class,
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
            'expires_at' => 'datetime',
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
     * Scope the query using the MailFolder instance.
     *
     * @param  Builder<MailboxSubscription>  $query
     */
    #[Scope]
    protected function expiresAt(Builder $query, DateTime $expiresAt): void
    {
        $query->where('expires_at', '<=', $expiresAt);
    }
}
