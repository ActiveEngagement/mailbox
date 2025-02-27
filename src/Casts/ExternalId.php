<?php

namespace Actengage\Mailbox\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ExternalId implements CastsAttributes
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
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [
            'external_id' => $value,
            'hash' => $value ? md5($value) : $value,
        ];
    }
}