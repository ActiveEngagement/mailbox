<?php

namespace Actengage\Mailbox\Data;

use Actengage\Mailbox\Enums\ComparisonOperator;
use Actengage\Mailbox\Enums\StringFunction;
use Carbon\Carbon;
use DateTime;
use Spatie\LaravelData\Data;
use Stringable;

/** @typescript Filter */
class Filter extends Data implements Stringable
{
    public function __construct(
        public string $field,
        public ComparisonOperator|StringFunction $operator,
        public string|DateTime|Carbon $value,
    ) {
        if($this->value instanceof DateTime) {
            $this->value = Carbon::make($this->value);
        }

        if($this->value instanceof Carbon) {
            $this->value = $this->value->format('Y-m-d\TH:i:s\Z');
        }
    }

    public function __tostring(): string
    {
        if($this->operator instanceof StringFunction) {
            return "{$this->operator->value}($this->field, '$this->value')";
        }

        return "$this->field {$this->operator->value} $this->value";
    }

    /**
     * Create a filter that compares the field and value with "eq".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function equals(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::Equals,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "ne".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function notEquals(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::NotEquals,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "gt".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function greaterThan(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::GreaterThan,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "ge".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function greaterThanOrEquals(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::GreaterThanOrEquals,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "lt".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function lessThan(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::LessThan,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "lt".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function lessThanOrEquals(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::LessThanOrEquals,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "startswith".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function startsWith(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: StringFunction::StartsWith,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "endswith".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function endsWith(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: StringFunction::EndsWith,
            value: $value,
        );
    }

    /**
     * Create a filter that compares the field and value with "contains".
     * 
     * @param string $field
     * @param string|DateTime|Carbon $value
     * @return Filter
     */
    public static function contains(string $field, string|DateTime|Carbon $value): static
    {
        return new self(
            field: $field,
            operator: StringFunction::Contains,
            value: $value,
        );
    }
}