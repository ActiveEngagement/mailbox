<?php

namespace Database\Factories;

use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxMessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

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

        $filename = str()->random(5) . '.html';

        return [
            'mailbox_id' => $mailbox,
            'message_id' => $message,
            'name' => $filename,
            'size' => 0,
            'content_type' => 'text/html',
            'disk' => 'public',
            'path' => "$message->hash/$filename",
            'last_modified_at' => now()
        ];
    }

    public function contents(string $contents)
    {
        return $this->state(function(array $attributes) use ($contents) {
            Storage::fake($attributes['disk']);
            Storage::disk($attributes['disk'])->put($attributes['path'], $contents);
            
            return [
                'size' =>  Storage::disk($attributes['disk'])->size($attributes['path'])
            ];
        });
    }
}
