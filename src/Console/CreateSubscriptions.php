<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Console;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Console\Command;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'mailbox:subscribe')]
class CreateSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'mailbox:subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the mailbox subscriptions.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string|null $email */
        $email = $this->option('email');

        $mailboxes = $email
            ? Mailbox::query()->email($email)->get()
            : Mailbox::all();

        foreach ($mailboxes as $mailbox) {
            $mailbox->subscriptions->each->delete();

            Client::connect($mailbox->connection);

            Subscriptions::subscribe($mailbox);

            $this->info(sprintf('The subscriptions %s have been resubscribed!', $mailbox->email));
        }

        return 0;
    }

    /**
     * Get the command arguments.
     *
     * @return array<int, array<int, mixed>>
     */
    #[Override]
    protected function getOptions(): array
    {
        return [
            ['email', 'e', InputOption::VALUE_OPTIONAL, 'The email address of the inbox.'],
        ];
    }
}
