<?php

namespace Actengage\Mailbox\Support;

use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\BroadcastsEvents;

trait BroadcastsEventsToOthers
{
    use BroadcastsEvents;

    /**
     * Create a new broadcastable model event for the model.
     */
    protected function newBroadcastableEvent(string $event): BroadcastableModelEventOccurred
    {
        return (new BroadcastableModelEventOccurred(
            $this, $event
        ))->dontBroadcastToCurrentUser();
    }
}