<?php

namespace Actengage\Mailbox\Models;

use Database\Factories\MailboxFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->hasMany(MailboxMessage::class);
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
     * @param string ...$email
     * @return void
     */
    public function scopeEmail(Builder $query, string ...$email): void
    {
        $query->whereIn('email', $email);
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
