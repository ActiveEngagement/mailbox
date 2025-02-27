<?php

namespace Actengage\Mailbox\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\ItemBody;

class Body implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return string|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }
 
    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if(is_null($value)) {
            return null;
        }

        if(is_string($value)) {
            return $value;
        }

        if($value instanceof ItemBody) {
            return $value->getContent();
        }

        throw new InvalidArgumentException("Unsupported type");
    }
}