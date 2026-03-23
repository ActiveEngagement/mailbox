<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Casts;

use Actengage\Mailbox\Data\FollowupFlag as FollowupFlagData;
use Actengage\Mailbox\Enums\FollowupFlagStatus;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Microsoft\Graph\Generated\Models\FollowupFlag as BaseFollowupFlag;

/** @implements CastsAttributes<FollowupFlagData|null, FollowupFlagData|BaseFollowupFlag|null> */
class FollowupFlag implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?FollowupFlagData
    {
        return FollowupFlagData::from([
            'status' => $value ?? FollowupFlagStatus::NotFlagged,
            'startDateTime' => $this->parseDate($attributes['started_at'] ?? null),
            'dueDateTime' => $this->parseDate($attributes['due_at'] ?? null),
            'completedDateTime' => $this->parseDate($attributes['completed_at'] ?? null),
        ]);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value instanceof BaseFollowupFlag) {
            $value = FollowupFlagData::fromFollowupFlag($value);
        }

        if ($value instanceof FollowupFlagData) {
            return [
                'flag' => $value->status,
                'started_at' => $value->startDateTime,
                'due_at' => $value->dueDateTime,
                'completed_at' => $value->completedDateTime,
            ];
        }

        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Date::parse($value);
        }

        if (\is_string($value) && $value !== '') {
            return Date::parse($value);
        }

        return null;
    }
}
