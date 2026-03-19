<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Data;

use InvalidArgumentException;
use Spatie\LaravelData\Data;
use Stringable;

/** @typescript EmailAddress */
class EmailAddress extends Data implements Stringable
{
    public function __construct(
        public string $email,
        public ?string $name,
    ) {
        //
    }

    /**
     * Get the TLD of the email address.
     */
    public function domain(): ?string
    {
        $parts = explode('@', $this->email);

        return $parts[1] ?? null;
    }

    /**
     * Create an email address from a string
     */
    public static function fromString(string $email): static
    {
        preg_match('/([^<]+)?(?:<(.+)>)?/', $email, $matches);
        if (count($matches) === 3) {
            return static::from([
                'email' => $matches[2],
                'name' => $matches[1],
            ]);
        }

        if (count($matches) === 2) {
            return static::from([
                'email' => $matches[1],
            ]);
        }

        throw new InvalidArgumentException($email.' is not a valid email address.');
    }

    /**
     * Cast the email to a string.
     */
    public function __toString(): string
    {
        if ($this->name) {
            return sprintf('%s<%s>', $this->name, $this->email);
        }

        return $this->email;
    }
}
