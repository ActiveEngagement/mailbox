<?php

use Actengage\Mailbox\Data\Conditional;
use Actengage\Mailbox\Data\Filter;

it('create a conditional with a single comparison', function() {
    expect((string) Conditional::and([
        Filter::equals('a', '1')
    ]))->toBe("a eq 1");
});

it('creates a conditional with multiple and single comparisons', function() {
    expect((string) Conditional::or([
        Conditional::and([
            Filter::equals('a', '1'),
            Filter::equals('b', '2')
        ]),
        Conditional::and([
            Filter::equals('c', '3')
        ]),
    ]))->toBe("((a eq 1 and b eq 2) or c eq 3)");
});

it('create a conditional that compares a and b', function() {
    expect((string) Conditional::and([
        Filter::equals('a', '1'),
        Filter::equals('b', '2')
    ]))->toBe("(a eq 1 and b eq 2)");
});

it('create a conditional that compares a or b', function() {
    expect((string) Conditional::or([
        Filter::equals('a', '1'),
        Filter::equals('b', '2')
    ]))->toBe("(a eq 1 or b eq 2)");
});

it('create a conditional that compares a or b and c or d', function() {
    expect((string) Conditional::and([
        Conditional::or([
            Filter::equals('a', '1'),
            Filter::equals('b', '2')
        ]),
        Conditional::or([
            Filter::equals('c', '3'),
            Filter::equals('d', '4')
        ])
    ]))->toBe("((a eq 1 or b eq 2) and (c eq 3 or d eq 4))");
});