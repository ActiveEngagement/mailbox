<?php

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Data\EmailAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\Recipient as BaseRecipient;

class Recipient implements CastsAttributes
{
    public bool $withoutObjectCaching = true;
    
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return EmailAddress|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EmailAddress
    {
        if(!$value) {
            return null;
        }

        return EmailAddress::fromString($value);
    }
 
    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if(is_null($value)) {
            return null;
        }

        if(is_string($value)) {
            return (string) EmailAddress::fromString($value);
        }

        if($value instanceof EmailAddress) {
            return (string) $value;
        }

        if($value instanceof BaseRecipient) {
            $address = $value->getEmailAddress();

            return (string) EmailAddress::from([
                'email' => $address->getAddress(),
                'name' => $address->getName()
            ]);
        }

        throw new InvalidArgumentException("Unsupported type");
    }
}