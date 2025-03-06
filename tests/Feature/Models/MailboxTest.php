<?php

use Actengage\Mailbox\Models\Mailbox;

it('it scopes the query by mailbox', function() {
    $mailbox = Mailbox::factory()->create();

    Mailbox::factory()->count(2)->create();

    expect(Mailbox::all())->toHaveCount(3);
    expect(Mailbox::mailbox($mailbox)->get())->toHaveCount(1);
    expect(Mailbox::mailbox($mailbox->id)->get())->toHaveCount(1);
});

it('it scopes the query by email', function() {
    $mailbox = Mailbox::factory()->create();

    Mailbox::factory()->count(2)->create();

    expect(Mailbox::all())->toHaveCount(3);
    expect(Mailbox::email($mailbox)->get())->toHaveCount(1);
    expect(Mailbox::email($mailbox->email)->get())->toHaveCount(1);
});
