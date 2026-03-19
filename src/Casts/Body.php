<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Microsoft\Graph\Generated\Models\ItemBody;

/** @implements CastsAttributes<string|null, string|ItemBody|null> */
class Body implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) ? $value : (string) json_encode($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return match (true) {
            $value === null => null,
            is_string($value) => $value,
            $value instanceof ItemBody => $value->getContent(),
        };
    }
}
