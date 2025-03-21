<?php

namespace Actengage\Mailbox\Http\Controllers;

use Actengage\Mailbox\Jobs\CreateMessage;
use Actengage\Mailbox\Jobs\DeleteMessage;
use Actengage\Mailbox\Jobs\SaveMessage;
use Actengage\Mailbox\Jobs\UpdateMessage;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class MessageWebhookController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Mailbox $mailbox): Response
    {
        foreach($request->input('value') as $event) {
            $job = match(Arr::get($event, 'changeType')) {
                'created' => CreateMessage::class,
                'updated' => UpdateMessage::class,
                'deleted' => DeleteMessage::class,
            };

            dispatch(new $job(
                $mailbox, Arr::get($event, 'resourceData.id')
            ));
        }

        return response('OK', 202);
    }
}
