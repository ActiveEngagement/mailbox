<?php

namespace Actengage\Mailbox\Http\Controllers;

use Actengage\Mailbox\Jobs\DeleteFolder;
use Actengage\Mailbox\Jobs\SaveFolder;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FolderWebhookController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Mailbox $mailbox)
    {
        foreach($request->input('value') as $event) {
            $job = match(Arr::get($event, 'changeType')) {
                'deleted' => DeleteFolder::class,
                'updated' => SaveFolder::class
            };

            dispatch(new $job(
                $mailbox, Arr::get($event, 'resourceData.id')
            ));
        }

        return response('OK', 202);
    }
}
