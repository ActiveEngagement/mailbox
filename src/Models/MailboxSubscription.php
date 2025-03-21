<?php

namespace Actengage\Mailbox\Models;

use Actengage\Mailbox\Events\MailboxSubscriptionCreated;
use Actengage\Mailbox\Events\MailboxSubscriptionDeleted;
use Actengage\Mailbox\Events\MailboxSubscriptionUpdated;
use Actengage\Mailbox\Observers\MailboxSubscriptionObserver;
use DateTime;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

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
    use BroadcastsEvents;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_id',
        'resource',
        'change_type',
        'notification_url',
        'expires_at'
    ];


    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array<string,class-string>
     */
    protected $dispatchesEvents = [
        'created' => MailboxSubscriptionCreated::class,
        'updated' => MailboxSubscriptionUpdated::class,
        'deleted' => MailboxSubscriptionDeleted::class,
    ];

    /**
     * The attributes that are cast.
     *
     * @return array
     */
    public function casts(): array
    {
        return [
            'expires_at' => 'datetime',
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
     * Scope the query using the MailFolder instance.
     *
     * @param Builder $query
     * @param DateTime $expiresAt
     * @return void
     */
    public function scopeExpiresAt(Builder $query, DateTime $expiresAt): void
    {
        $query->where('expires_at', '<=', $expiresAt);
    }
}
