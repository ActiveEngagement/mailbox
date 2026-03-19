<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Http\Controllers;

use Actengage\Mailbox\Jobs\DeleteFolder;
use Actengage\Mailbox\Jobs\SaveFolder;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class FolderWebhookController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Mailbox $mailbox): Response
    {
        /** @var array<int, array<string, mixed>> $events */
        $events = $request->input('value');

        foreach ($events as $event) {
            /** @var string $changeType */
            $changeType = Arr::get($event, 'changeType');
            /** @var string $resourceId */
            $resourceId = Arr::get($event, 'resourceData.id');

            $job = match ($changeType) {
                'deleted' => DeleteFolder::class,
                'updated' => SaveFolder::class,
                default => null,
            };

            if ($job === null) {
                continue;
            }

            dispatch(new $job($mailbox, $resourceId));
        }

        return response('OK', 202);
    }
}
