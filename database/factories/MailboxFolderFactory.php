<?php

namespace Database\Factories;

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \Actengage\Mailbox\MailboxMessage
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class MailboxFolderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = MailboxFolder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailbox_id' => Mailbox::factory(),
            'external_id' => fake()->uuid(),
            'name' => fake()->name(),
            'is_hidden' => false,
            'is_favorite' => false,
        ];
    }
}
