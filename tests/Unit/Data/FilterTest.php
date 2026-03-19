<?php

use Actengage\Mailbox\Data\Filter;
use Actengage\Mailbox\Enums\ComparisonOperator;

it('converts the filter to a string', function (): void {
    expect((string) new Filter(
        field: 'name',
        operator: ComparisonOperator::Equals,
        value: 'foo'
    ))->toBe('name eq foo');
});

it('creates a filter comparing a field and value with "eq"', function (): void {
    expect((string) Filter::equals(
        field: 'a',
        value: 'b'
    ))->toBe('a eq b');
});

it('creates a filter comparing a field and value with "ne"', function (): void {
    expect((string) Filter::notEquals(
        field: 'a',
        value: 'b'
    ))->toBe('a ne b');
});

it('creates a filter comparing a field and value with "gt"', function (): void {
    expect((string) Filter::greaterThan(
        field: 'a',
        value: 'b'
    ))->toBe('a gt b');
});

it('creates a filter comparing a field and value with "ge"', function (): void {
    expect((string) Filter::greaterThanOrEquals(
        field: 'a',
        value: 'b'
    ))->toBe('a ge b');
});

it('creates a filter comparing a field and value with "lt"', function (): void {
    expect((string) Filter::lessThan(
        field: 'a',
        value: 'b'
    ))->toBe('a lt b');
});

it('creates a filter comparing a field and value with "le"', function (): void {
    expect((string) Filter::lessThanOrEquals(
        field: 'a',
        value: 'b'
    ))->toBe('a le b');
});

it('creates a filter comparing a field and value with "startswith"', function (): void {
    expect((string) Filter::startsWith(
        field: 'subject',
        value: 'test',
    ))->toBe("startswith(subject, 'test')");
});

it('creates a filter comparing a field and value with "endswith"', function (): void {
    expect((string) Filter::endsWith(
        field: 'subject',
        value: 'test',
    ))->toBe("endswith(subject, 'test')");
});

it('creates a filter comparing a field and value with "contains"', function (): void {
    expect((string) Filter::contains(
        field: 'subject',
        value: 'test',
    ))->toBe("contains(subject, 'test')");
});

it('creates a filter comparing a field and DateTime with "gt"', function (): void {
    expect((string) Filter::greaterThan(
        field: 'receivedDateTime',
        value: new DateTime('2025-01-10'),
    ))->toBe('receivedDateTime gt 2025-01-10T00:00:00Z');
});
