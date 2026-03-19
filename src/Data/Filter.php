<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Data;

use Actengage\Mailbox\Enums\ComparisonOperator;
use Actengage\Mailbox\Enums\StringFunction;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Date;
use Spatie\LaravelData\Data;
use Stringable;

/** @typescript Filter */
final class Filter extends Data implements Stringable
{
    public function __construct(
        public string $field,
        public ComparisonOperator|StringFunction $operator,
        public string $value,
    ) {
        //
    }

    /**
     * Resolve the value to a string.
     */
    private static function resolveValue(string|DateTime|Carbon $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d\TH:i:s\Z');
        }

        if ($value instanceof DateTime) {
            $carbon = Date::make($value);

            return $carbon instanceof Carbon ? $carbon->format('Y-m-d\TH:i:s\Z') : $value->format('Y-m-d\TH:i:s\Z');
        }

        return $value;
    }

    public function __toString(): string
    {
        if ($this->operator instanceof StringFunction) {
            return sprintf("%s(%s, '%s')", $this->operator->value, $this->field, $this->value);
        }

        return sprintf('%s %s %s', $this->field, $this->operator->value, $this->value);
    }

    /**
     * Create a filter that compares the field and value with "eq".
     */
    public static function equals(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::Equals,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "ne".
     */
    public static function notEquals(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::NotEquals,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "gt".
     */
    public static function greaterThan(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::GreaterThan,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "ge".
     */
    public static function greaterThanOrEquals(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::GreaterThanOrEquals,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "lt".
     */
    public static function lessThan(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::LessThan,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "lt".
     */
    public static function lessThanOrEquals(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: ComparisonOperator::LessThanOrEquals,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "startswith".
     */
    public static function startsWith(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: StringFunction::StartsWith,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "endswith".
     */
    public static function endsWith(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: StringFunction::EndsWith,
            value: self::resolveValue($value),
        );
    }

    /**
     * Create a filter that compares the field and value with "contains".
     */
    public static function contains(string $field, string|DateTime|Carbon $value): self
    {
        return new self(
            field: $field,
            operator: StringFunction::Contains,
            value: self::resolveValue($value),
        );
    }
}
