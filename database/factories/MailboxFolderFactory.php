<?php

declare(strict_types=1);

namespace Database\Factories;

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailboxFolder>
 */
class MailboxFolderFactory extends Factory
{
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
