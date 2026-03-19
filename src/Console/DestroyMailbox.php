<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Console;

use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Console\Command;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'mailbox:destroy')]
class DestroyMailbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'mailbox:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Destory the mailbox for the given email address.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $email */
        $email = $this->argument('email');

        $mailbox = Mailbox::query()->email($email)->firstOrFail();
        $mailbox->subscriptions->each->delete();
        $mailbox->delete();

        $this->info($mailbox->email.' was destroyed!');

        return 0;
    }

    /**
     * Get the command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    #[Override]
    protected function getArguments(): array
    {
        return [
            ['email', InputArgument::REQUIRED, 'The email address of the inbox.'],
        ];
    }
}
