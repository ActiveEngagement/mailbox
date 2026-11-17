<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Support;

use Illuminate\Queue\Middleware\WithoutOverlapping;

trait PreventsMessageOverlap
{
    public int $tries = 10;

    public int $maxExceptions = 1;

    /**
     * @return array<int, WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping(sprintf('mailbox-message:%s:%s', $this->mailbox->id, $this->id))
                ->shared()
                ->releaseAfter(10)
                ->expireAfter(180),
        ];
    }
}
