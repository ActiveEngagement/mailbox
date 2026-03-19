<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** @implements CastsAttributes<string|null, mixed> */
class ExternalId implements CastsAttributes
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
     * @return array<string, string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $stringValue = $value === null ? null : (is_string($value) ? $value : (string) json_encode($value));

        return [
            'external_id' => $stringValue,
            'hash' => $stringValue !== null && $stringValue !== '' ? md5($stringValue) : $stringValue,
        ];
    }
}
