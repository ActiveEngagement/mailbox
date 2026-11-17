<?php

use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Jobs\CreateMessage;
use Actengage\Mailbox\Jobs\UpdateMessage;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Support\Facades\Cache;

it('gives create and update jobs for the same message one lock', function (): void {
    $mailbox = Mailbox::factory()->create();
    $create = new CreateMessage($mailbox, 'ext-id');
    $update = new UpdateMessage($mailbox, 'ext-id');
    $otherMessage = new UpdateMessage($mailbox, 'other-ext-id');

    $createKey = $create->middleware()[0]->getLockKey($create);

    expect($update->middleware()[0]->getLockKey($update))->toBe($createKey)
        ->and($otherMessage->middleware()[0]->getLockKey($otherMessage))->not->toBe($createKey);
});

it('releases the job while another job holds the message lock', function (): void {
    $mailbox = Mailbox::factory()->create();
    $create = new CreateMessage($mailbox, 'ext-id');
    $update = new UpdateMessage($mailbox, 'ext-id')->withFakeQueueInteractions();
    [$overlap] = $update->middleware();

    Cache::lock($overlap->getLockKey($create), 180)->acquire();
    Messages::shouldReceive('save')->never();

    $overlap->handle($update, fn (UpdateMessage $job) => $job->handle());

    $update->assertReleased(10);
    expect($update->tries)->toBeGreaterThan(1)
        ->and($update->maxExceptions)->toBe(1);
});
