<?php

namespace Database\Factories;

use Actengage\Mailbox\Enums\Importance;
use Actengage\Mailbox\Models\Mailbox;
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
class MailboxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Mailbox::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->email(),
            'connection' => 'default'
        ];
    }
}
