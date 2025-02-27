<?php

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Data\FollowupFlag as FollowupFlagData;
use Actengage\Mailbox\Enums\Importance as ImportanceEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\Importance as BaseImportance;

class Importance implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return FollowupFlagData|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ImportanceEnum
    {
        if($value instanceof ImportanceEnum) {
            return $value;
        }
        
        return ImportanceEnum::from($value);
    }
 
    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string,mixed>|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ImportanceEnum
    {
        if(is_null($value)) {
            return ImportanceEnum::Normal;
        }

        if(is_string($value)) {
            return ImportanceEnum::from($value);
        }

        if($value instanceof ImportanceEnum) {
            return $value;
        }

        if($value instanceof BaseImportance) {
            return ImportanceEnum::from($value->value());
        }

        throw new InvalidArgumentException("Unsupported type");
    }
}