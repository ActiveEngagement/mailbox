<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Data\EmailAddress;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\Recipient;

/** @implements CastsAttributes<Collection<int, EmailAddress>|null, iterable<mixed>|string|null> */
class Recipients implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return Collection<int, EmailAddress>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Collection
    {
        if (is_null($value)) {
            return null;
        }

        /** @var array<int, string> $decoded */
        $decoded = json_decode(is_string($value) ? $value : (string) json_encode($value), true);

        return collect($decoded)->map(fn (string $value): EmailAddress => EmailAddress::fromString($value));
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
            $value = [$value];
        }

        $recipients = [];

        foreach ($value as $recipient) {
            if ($recipient instanceof Recipient) {
                $address = $recipient->getEmailAddress();

                if (! $address instanceof \Microsoft\Graph\Generated\Models\EmailAddress) {
                    continue;
                }

                $recipients[] = (string) EmailAddress::from([
                    'email' => (string) $address->getAddress(),
                    'name' => $address->getName(),
                ]);

                continue;
            }

            if ($recipient instanceof EmailAddress) {
                $recipients[] = (string) $recipient;

                continue;
            }

            if (is_array($recipient)) {
                $recipients[] = (string) EmailAddress::from($recipient);

                continue;
            }

            if (is_string($recipient)) {
                $recipients[] = (string) EmailAddress::fromString($recipient);

                continue;
            }

            throw new InvalidArgumentException('Unsupported type');
        }

        if ($recipients === []) {
            return null;
        }

        return (string) json_encode($recipients);
    }
}
