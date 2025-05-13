<?php

namespace Actengage\Mailbox\Data;

use Actengage\Mailbox\Enums\ComparisonOperator;
use Actengage\Mailbox\Enums\LogicalOperator;
use Spatie\LaravelData\Data;
use Stringable;

/** @typescript Filter */
class Conditional extends Data implements Stringable
{
    /**
     * Create the conditional.
     * 
     * @param \Actengage\Mailbox\Enums\LogicalOperator|null $operator
     * @param array<int,Conditional|Filter> $filters
     */
    public function __construct(
        public LogicalOperator|null $operator = null,
        public array $filters = [],
    ) {
        //
    }

    public function __tostring(): string
    {
        if(count($this->filters) <= 1) {
            return implode(
                separator: " {$this->operator->value} ", array: $this->filters
            );
        }

        return sprintf("(%s)", implode(
            separator: " {$this->operator->value} ", array: $this->filters
        ));
    }

    /**
     * Create an "and" conditional.
     * 
     * @param array<int,Conditional|Filter> $filters
     * @return static
     */
    public static function and(array $filters): static
    {
        return new static(LogicalOperator::And, $filters);
    }

    /**
     * Create an "or" conditional.
     * 
     * @param array<int,Conditional|Filter> $filters
     * @return static
     */
    public static function or(array $filters): static
    {
        return new static(LogicalOperator::Or, $filters);
    }
}