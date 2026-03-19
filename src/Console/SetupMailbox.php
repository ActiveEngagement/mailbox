<?php

declare(strict_types=1);

namespace Actengage\Mailbox\Console;

use Actengage\Mailbox\Data\Conditional;
use Actengage\Mailbox\Data\Filter;
use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Actengage\Mailbox\Models\MailboxFolder;
use Actengage\Mailbox\Models\MailboxMessage;
use Actengage\Mailbox\Models\MailboxSubscription;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Date;
use Microsoft\Graph\Generated\Models\Message;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'mailbox:setup')]
class SetupMailbox extends Command implements PromptsForMissingInput
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mailbox:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup a mailbox for the given email address.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $progress = $this->output->createProgressBar(4);
        $progress->start();
        $progress->setFormat("%message%\n\n %current%/%max% [%bar%] %percent:3s%%");
        $progress->setMessage('Creating Mailbox');

        $mailbox = $this->createMailbox();

        $progress->setMessage('Creating Mailbox Folders');
        $progress->advance();

        $this->createMailboxFolders($mailbox);

        $progress->setMessage('Creating Messages');
        $progress->advance();

        if (! $this->option('skip')) {
            /** @var string|null $after */
            $after = $this->option('after');

            $filter = $after
                ? Filter::greaterThanOrEquals(
                    field: 'receivedDateTime',
                    value: Date::parse($after)
                )
                : null;

            $this->createMailboxMessages(
                mailbox: $mailbox,
                filter: $filter,
            );
        }

        $progress->setMessage('Creating Subscriptions');
        $progress->advance();

        $this->createMailboxSubscriptions($mailbox);

        $progress->finish();
        $progress->clear();

        $this->info($mailbox->email.' was setup!');

        return 0;
    }

    /**
     * Create the mailbox if it doesn't exist.
     */
    protected function createMailbox(): Mailbox
    {
        /** @var Mailbox */
        return Mailbox::withoutBroadcasting(fn () => Mailbox::query()->firstOrCreate([
            'email' => $this->argument('email'),
        ], [
            'connection' => Client::connection(),
        ]));
    }

    /**
     * Create the mailbox folders if they don't exist.
     */
    protected function createMailboxFolders(Mailbox $mailbox): void
    {
        /** @var string $email */
        $email = $this->argument('email');

        MailboxFolder::withoutBroadcasting(function () use ($mailbox, $email): void {
            $folders = Folders::all($email);

            foreach ($folders as $folder) {
                Folders::save($mailbox, $folder);
            }
        });
    }

    /**
     * Create the mailbox messages if they don't exist.
     */
    protected function createMailboxMessages(Mailbox $mailbox, Conditional|Filter|string|null $filter = null): void
    {
        /** @var string $email */
        $email = $this->argument('email');

        MailboxMessage::withoutBroadcasting(function () use ($mailbox, $email, $filter): void {
            Messages::all(
                userId: $email,
                iterator: function (Message $message) use ($mailbox): void {
                    Messages::save($mailbox, $message);
                },
                filter: $filter,
            );
        });
    }

    /**
     * Create the mailbox subscriptions if they don't exist.
     */
    protected function createMailboxSubscriptions(Mailbox $mailbox): void
    {
        MailboxSubscription::withoutBroadcasting(function () use ($mailbox): void {
            $mailbox->subscriptions->each->delete();

            Subscriptions::subscribe($mailbox);
        });
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

    /**
     * Get the command options.
     *
     * @return array<int, array<int, mixed>>
     */
    #[Override]
    protected function getOptions(): array
    {
        return [
            ['connection', 'c', InputOption::VALUE_OPTIONAL, 'The name of the connection to test.', 'default'],
            ['after', 'a', InputOption::VALUE_OPTIONAL, 'Filter messages received on or after this date.', null],
            ['skip', 's', InputOption::VALUE_NONE, 'Skip creating existing messages.'],
        ];
    }
}
