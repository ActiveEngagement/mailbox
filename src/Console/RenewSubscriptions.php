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

#[AsCommand(name: 'mailbox:resubscribe')]
class RenewSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'mailbox:resubscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resubscribe to the mailbox subscriptions.';

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
            $hasSubscriptions = $mailbox->subscriptions()
                ->expiresAt(now()->addHour())
                ->exists();

            if (! $hasSubscriptions) {
                $this->warn($mailbox->email.' has no subscriptions to renew!');

                continue;
            }

            $subscriptions = $mailbox->subscriptions;

            Client::connect($mailbox->connection);

            Subscriptions::subscribe($mailbox);

            $subscriptions->each->delete();

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
