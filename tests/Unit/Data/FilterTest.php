<?php

use Actengage\Mailbox\Data\Filter;
use Actengage\Mailbox\Enums\ComparisonOperator;

it('converts the filter to a string', function() {
    expect((string) new Filter(
        field: 'name',
        operator: ComparisonOperator::Equals,
        value: 'foo'
    ))->toBe('name eq foo');
});

it('creates a filter comparing a field and value with "eq"', function() {
    expect((string) Filter::equals(
        field: 'a',
        value: 'b'
    ))->toBe('a eq b');
});

it('creates a filter comparing a field and value with "ne"', function() {
    expect((string) Filter::notEquals(
        field: 'a',
        value: 'b'
    ))->toBe('a ne b');
});

it('creates a filter comparing a field and value with "gt"', function() {
    expect((string) Filter::greaterThan(
        field: 'a',
        value: 'b'
    ))->toBe('a gt b');
});

it('creates a filter comparing a field and value with "ge"', function() {
    expect((string) Filter::greaterThanOrEquals(
        field: 'a',
        value: 'b'
    ))->toBe('a ge b');
});

it('creates a filter comparing a field and value with "lt"', function() {
    expect((string) Filter::lessThan(
        field: 'a',
        value: 'b'
    ))->toBe('a lt b');
});

it('creates a filter comparing a field and value with "le"', function() {
    expect((string) Filter::lessThanOrEquals(
        field: 'a',
        value: 'b'
    ))->toBe('a le b');
});

it('creates a filter comparing a field and value with "startswith"', function() {
    expect((string) Filter::startsWith(
        field: 'subject',
        value: 'test',
    ))->toBe('startswith(subject, \'test\')');
});

it('creates a filter comparing a field and value with "endswith"', function() {
    expect((string) Filter::endsWith(
        field: 'subject',
        value: 'test',
    ))->toBe('endswith(subject, \'test\')');
});

it('creates a filter comparing a field and value with "contains"', function() {
    expect((string) Filter::contains(
        field: 'subject',
        value: 'test',
    ))->toBe('contains(subject, \'test\')');
});

it('creates a filter comparing a field and DateTime with "gt"', function() {
    expect((string) Filter::greaterThan(
        field: 'receivedDateTime',
        value: new DateTime('2025-01-10'),
    ))->toBe('receivedDateTime gt 2025-01-10T00:00:00Z');
});