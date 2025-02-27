<?php

namespace Database\Factories;

use Actengage\Mailbox\Enums\Importance;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\FollowupFlag;
use Microsoft\Graph\Generated\Models\FollowupFlagStatus;
use Microsoft\Graph\Generated\Models\ItemBody;

/**
 * @template TModel of \Actengage\Mailbox\MailboxMessage
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class MailboxMessageAttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = MailboxMessageAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mailbox = Mailbox::factory()->create();
        $message = MailboxMessage::factory()->for($mailbox)->create();

        return [
            'mailbox_id' => $mailbox,
            'message_id' => $message,
            'name' => 'test.html',
            'size' => 100,
            'content_type' => 'text/html',
            'disk' => 'public',
            'path' => 'test',
            'last_modified_at' => now()
        ];
    }
}
