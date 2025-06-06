<?php

namespace Database\Factories;

use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Database\Eloquent\Factories\Factory;

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
