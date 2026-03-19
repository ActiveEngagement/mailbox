<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Data;

use Actengage\Mailbox\Enums\LogicalOperator;
use Spatie\LaravelData\Data;
use Stringable;

/** @typescript Conditional */
final class Conditional extends Data implements Stringable
{
    /**
     * Create the conditional.
     *
     * @param  array<int,Conditional|Filter>  $filters
     */
    public function __construct(
        public ?LogicalOperator $operator = null,
        public array $filters = [],
    ) {
        //
    }

    public function __toString(): string
    {
        $separator = sprintf(' %s ', $this->operator instanceof LogicalOperator ? $this->operator->value : 'and');

        if (count($this->filters) <= 1) {
            return implode(
                separator: $separator, array: $this->filters
            );
        }

        return sprintf('(%s)', implode(
            separator: $separator, array: $this->filters
        ));
    }

    /**
     * Create an "and" conditional.
     *
     * @param  array<int,Conditional|Filter>  $filters
     */
    public static function and(array $filters): self
    {
        return new self(operator: LogicalOperator::And, filters: $filters);
    }

    /**
     * Create an "or" conditional.
     *
     * @param  array<int,Conditional|Filter>  $filters
     */
    public static function or(array $filters): self
    {
        return new self(operator: LogicalOperator::Or, filters: $filters);
    }
}
