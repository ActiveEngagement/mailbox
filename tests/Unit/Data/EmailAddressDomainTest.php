<?php

use Actengage\Mailbox\Data\EmailAddress;

it('returns the domain of the email', function (): void {
    $email = EmailAddress::from(['email' => 'user@example.com', 'name' => 'User']);

    expect($email->domain())->toBe('example.com');
});

it('returns null when no @ symbol is present', function (): void {
    $email = EmailAddress::from(['email' => 'invalid', 'name' => null]);

    expect($email->domain())->toBeNull();
});

it('casts to string with name', function (): void {
    $email = EmailAddress::from(['email' => 'user@example.com', 'name' => 'User']);

    expect((string) $email)->toBe('User<user@example.com>');
});

it('casts to string without name', function (): void {
    $email = EmailAddress::from(['email' => 'user@example.com', 'name' => null]);

    expect((string) $email)->toBe('user@example.com');
});

it('throws for an invalid email string', function (): void {
    EmailAddress::fromString('');
})->throws(InvalidArgumentException::class);
