<?php

use Actengage\Mailbox\Casts\ExternalId;
use Actengage\Mailbox\Models\MailboxMessage;

it('gets null external id', function (): void {
    $cast = new ExternalId;
    $model = new MailboxMessage;

    expect($cast->get($model, 'external_id', null, []))->toBeNull();
});

it('gets string external id', function (): void {
    $cast = new ExternalId;
    $model = new MailboxMessage;

    expect($cast->get($model, 'external_id', 'abc123', []))->toBe('abc123');
});

it('gets non-string external id as json', function (): void {
    $cast = new ExternalId;
    $model = new MailboxMessage;

    expect($cast->get($model, 'external_id', 123, []))->toBe('123');
});

it('sets external id with hash', function (): void {
    $cast = new ExternalId;
    $model = new MailboxMessage;

    $result = $cast->set($model, 'external_id', 'abc123', []);

    expect($result)->toBe([
        'external_id' => 'abc123',
        'hash' => md5('abc123'),
    ]);
});

it('sets null external id', function (): void {
    $cast = new ExternalId;
    $model = new MailboxMessage;

    $result = $cast->set($model, 'external_id', null, []);

    expect($result)->toBe([
        'external_id' => null,
        'hash' => null,
    ]);
});

it('sets non-string external id as json', function (): void {
    $cast = new ExternalId;
    $model = new MailboxMessage;

    $result = $cast->set($model, 'external_id', ['id' => 123], []);
    $expected = json_encode(['id' => 123]);

    expect($result)->toBe([
        'external_id' => $expected,
        'hash' => md5((string) $expected),
    ]);
});
