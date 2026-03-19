<?php

use Actengage\Mailbox\Jobs\CreateMessage;
use Actengage\Mailbox\Jobs\DeleteMessage;
use Actengage\Mailbox\Jobs\UpdateMessage;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Support\Facades\Bus;

it('dispatches a CreateMessage job for created events', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.messages', $mailbox), [
        'value' => [
            ['changeType' => 'created', 'resourceData' => ['id' => 'msg-1']],
        ],
    ])->assertStatus(202);

    Bus::assertDispatched(CreateMessage::class, fn (CreateMessage $job): bool => $job->id === 'msg-1' && $job->mailbox->is($mailbox));
});

it('dispatches an UpdateMessage job for updated events', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.messages', $mailbox), [
        'value' => [
            ['changeType' => 'updated', 'resourceData' => ['id' => 'msg-2']],
        ],
    ])->assertStatus(202);

    Bus::assertDispatched(UpdateMessage::class, fn (UpdateMessage $job): bool => $job->id === 'msg-2');
});

it('dispatches a DeleteMessage job for deleted events', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.messages', $mailbox), [
        'value' => [
            ['changeType' => 'deleted', 'resourceData' => ['id' => 'msg-3']],
        ],
    ])->assertStatus(202);

    Bus::assertDispatched(DeleteMessage::class, fn (DeleteMessage $job): bool => $job->id === 'msg-3');
});

it('ignores unknown change types', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.messages', $mailbox), [
        'value' => [
            ['changeType' => 'unknown', 'resourceData' => ['id' => 'msg-4']],
        ],
    ])->assertStatus(202);

    Bus::assertNotDispatched(CreateMessage::class);
    Bus::assertNotDispatched(UpdateMessage::class);
    Bus::assertNotDispatched(DeleteMessage::class);
});

it('dispatches multiple jobs for multiple events', function (): void {
    Bus::fake();

    $mailbox = Mailbox::factory()->create();

    $this->post(route('mailbox.webhooks.messages', $mailbox), [
        'value' => [
            ['changeType' => 'created', 'resourceData' => ['id' => 'msg-a']],
            ['changeType' => 'deleted', 'resourceData' => ['id' => 'msg-b']],
        ],
    ])->assertStatus(202);

    Bus::assertDispatched(CreateMessage::class);
    Bus::assertDispatched(DeleteMessage::class);
});
