<?php

namespace Database\Factories;

use Actengage\Mailbox\Enums\Importance;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\DateTimeTimeZone;
use Microsoft\Graph\Generated\Models\FollowupFlag;
use Microsoft\Graph\Generated\Models\FollowupFlagStatus;
use Microsoft\Graph\Generated\Models\ItemBody;

/**
 * @template TModel of \Actengage\Mailbox\Models\MailboxMessage
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class MailboxMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = MailboxMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = new ItemBody();
        $body->setContent(fake()->randomHtml());
        $body->setContentType(new BodyType('html'));

        $flagStatus = new FollowupFlagStatus(FollowupFlagStatus::FLAGGED);

        $flagStartDate = new DateTimeTimeZone();
        $flagStartDate->setDateTime((string) now());
        $flagStartDate->setTimeZone('UTC');

        $flagDueDate = new DateTimeTimeZone();
        $flagDueDate->setDateTime((string) now()->addDay(1));
        $flagDueDate->setTimeZone('UTC');

        $flag = new FollowupFlag();
        $flag->setFlagStatus($flagStatus);
        $flag->setStartDateTime($flagStartDate);
        $flag->setDueDateTime($flagDueDate);

        return [
            'mailbox_id' => Mailbox::factory(),
            'folder_id' => MailboxFolder::factory(),
            'external_id' => fake()->uuid(),
            'conversation_id' => fake()->uuid(),
            'conversation_index' => fake()->uuid(),
            'is_read' => false,
            'is_draft' => false,
            'flag' => $flag,
            'importance' => Importance::Normal,
            'to' => $this->email(),
            'from' => $this->email(),
            'cc' => [$this->email(), $this->emailWithoutName()],
            'bcc' => [$this->email(), $this->emailWithoutName()],
            'subject' => fake()->sentence(),
            'body' => $body
        ];
    }

    /**
     * Create an email with a name.
     *
     * @return string
     */
    protected function email(): string
    {
        return fake()->name() . '<' . fake()->email() . '>';
    }

    /**
     * Create an email without a name.
     *
     * @return string
     */
    protected function emailWithoutName(): string
    {
        return fake()->email();
    }
}
