<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Enums\Importance as ImportanceEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Microsoft\Graph\Generated\Models\Importance as BaseImportance;

/** @implements CastsAttributes<ImportanceEnum, ImportanceEnum|BaseImportance|string|null> */
class Importance implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ImportanceEnum
    {
        if ($value instanceof ImportanceEnum) {
            return $value;
        }

        if (is_string($value)) {
            return ImportanceEnum::from($value);
        }

        return ImportanceEnum::Normal;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ImportanceEnum
    {
        return match (true) {
            $value === null => ImportanceEnum::Normal,
            is_string($value) => ImportanceEnum::from($value),
            $value instanceof ImportanceEnum => $value,
            $value instanceof BaseImportance => ImportanceEnum::from($value->value()),
        };
    }
}
