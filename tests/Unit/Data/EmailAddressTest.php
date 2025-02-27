<?php

use Actengage\Mailbox\Data\EmailAddress;

it('can be created from a string', function() {
    $address = EmailAddress::fromString('test@test.com');

    expect($address->email)->toBe('test@test.com');
    expect($address->name)->toBeNull();
    expect((string) $address)->toBe('test@test.com');

    $address = EmailAddress::fromString('John F. Doe<test@test.com>');

    expect($address->email)->toBe('test@test.com');
    expect($address->name)->toBe('John F. Doe');
    expect((string) $address)->toBe('John F. Doe<test@test.com>');
});