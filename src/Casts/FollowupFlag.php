<?php

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Data\FollowupFlag as FollowupFlagData;
use Actengage\Mailbox\Enums\FollowupFlagStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Microsoft\Graph\Generated\Models\FollowupFlag as BaseFollowupFlag;

class FollowupFlag implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @return FollowupFlagData|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?FollowupFlagData
    {
        return FollowupFlagData::from([
            'status' => $value ?? FollowupFlagStatus::NotFlagged,
            'startDateTime' => $attributes['started_at'],
            'dueDateTime' => $attributes['due_at'],
            'completedDateTime' => $attributes['completed_at'],
        ]);
    }
 
    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string,mixed>|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if(is_null($value)) {
            return null;
        }

        if($value instanceof BaseFollowupFlag) {
            $value = FollowupFlagData::fromFollowupFlag($value);
        }

        if($value instanceof FollowupFlagData) {
            return [
                'flag' => $value->status,
                'started_at' => $value->startDateTime,
                'due_at' => $value->dueDateTime,
                'completed_at' => $value->completedDateTime,
            ];
        }

        throw new InvalidArgumentException("Unsupported type");
    }
}