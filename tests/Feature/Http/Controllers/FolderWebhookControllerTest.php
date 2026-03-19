<?php

use Actengage\Mailbox\Jobs\DeleteFolder;
use Actengage\Mailbox\Jobs\SaveFolder;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Support\Facades\Bus;

it('dispatches a SaveFolder job for updated events', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.folders', $mailbox), [
        'value' => [
            ['changeType' => 'updated', 'resourceData' => ['id' => 'folder-1']],
        ],
    ])->assertStatus(202);

    Bus::assertDispatched(SaveFolder::class, fn (SaveFolder $job): bool => $job->id === 'folder-1' && $job->mailbox->is($mailbox));
});

it('dispatches a DeleteFolder job for deleted events', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.folders', $mailbox), [
        'value' => [
            ['changeType' => 'deleted', 'resourceData' => ['id' => 'folder-2']],
        ],
    ])->assertStatus(202);

    Bus::assertDispatched(DeleteFolder::class, fn (DeleteFolder $job): bool => $job->id === 'folder-2');
});

it('ignores unknown change types', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.folders', $mailbox), [
        'value' => [
            ['changeType' => 'created', 'resourceData' => ['id' => 'folder-3']],
        ],
    ])->assertStatus(202);

    Bus::assertNotDispatched(SaveFolder::class);
    Bus::assertNotDispatched(DeleteFolder::class);
});

it('returns validation token when present', function (): void {
    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.folders', $mailbox).'?validationToken=test-token')
        ->assertStatus(200)
        ->assertSee('test-token');
});
