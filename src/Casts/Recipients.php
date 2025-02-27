<?php

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Data\EmailAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\Recipient;

class Recipients implements CastsAttributes
{
    public bool $withoutObjectCaching = true;
    
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return Collection<EmailAddress>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Collection
    {
        if(is_null($value)) {
            return null;
        }

        return collect(json_decode($value, true))->map(function(string $value) {
            return EmailAddress::fromString($value);
        });
    }
 
    /**
     * Prepare the given value for storage.
     *
     * @param  array<string,mixed>  $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if(is_null($value)) {
            return null;
        }

        if(is_string($value)) {
            $value = [$value];
        }

        $recipients = [];

        foreach($value as $recipient) {
            if($recipient instanceof Recipient) {
                $address = $recipient->getEmailAddress();

                $recipients[] = (string) EmailAddress::from([
                    'email' => $address->getAddress(),
                    'name' => $address->getName()
                ]);

                continue;
            }         

            if($recipient instanceof EmailAddress) {
                $recipients[] = (string) $recipient;

                continue;
            }

            if(is_string($recipient)) {
                $recipients[] = (string) EmailAddress::fromString($recipient);

                continue;
            }

            throw new InvalidArgumentException("Unsupported type");
        }

        if(!count($recipients)) {
            return null;
        }

        return json_encode($recipients);
    }
}