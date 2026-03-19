<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Data\EmailAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Microsoft\Graph\Generated\Models\Recipient as BaseRecipient;

/** @implements CastsAttributes<EmailAddress|null, EmailAddress|BaseRecipient|string|array<string, string>|null> */
class Recipient implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EmailAddress
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return EmailAddress::fromString($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            return (string) EmailAddress::fromString($value);
        }

        if ($value instanceof EmailAddress) {
            return (string) $value;
        }

        if ($value instanceof BaseRecipient) {
            $address = $value->getEmailAddress();

            if (! $address instanceof \Microsoft\Graph\Generated\Models\EmailAddress) {
                return null;
            }

            return (string) EmailAddress::from([
                'email' => (string) $address->getAddress(),
                'name' => $address->getName(),
            ]);
        }

        return (string) EmailAddress::from($value);
    }
}
