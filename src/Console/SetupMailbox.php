<?php

namespace Actengage\Mailbox\Console;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Folders;
use Actengage\Mailbox\Facades\Messages;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
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
    protected $command = 'mailbox:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup a mailbox for the given email address.';

    /**
     * Execute the console command.
     *
     * @return bool|null
     *
     * @throws \InvalidArgumentException
     */
    public function handle()
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

        $this->createMailboxMessages($mailbox);

        $progress->setMessage('Creating Subscriptions');
        $progress->advance();

        $this->createMailboxSubscriptions($mailbox);

        $progress->finish();
        $progress->clear();

        $this->info("$mailbox->email was setup!");
    }

    /**
     * Create the mailbox if it doesn't exist.
     *
     * @return Mailbox
     */
    protected function createMailbox(): Mailbox
    {
        return Mailbox::firstOrCreate([
            'email' => $this->argument('email')
        ], [
            'connection' => Client::connection()
        ]);
    }

    /**
     * Create the mailbox folders if they don't exist.
     *
     * @param Mailbox $mailbox
     * @return void
     */
    protected function createMailboxFolders(Mailbox $mailbox): void
    {
        $folders = Folders::all($this->argument('email'));

        foreach($folders as $folder) {
            Folders::save($mailbox, $folder);
        }
    }

    /**
     * Create the mailbox messages if they don't exist.
     *
     * @param Mailbox $mailbox
     * @return void
     */
    protected function createMailboxMessages(Mailbox $mailbox): void
    {
        $messages = Messages::all($this->argument('email'));

        foreach($messages as $message) {
            Messages::save($mailbox, $message);
        }
    }

    /**
     * Create the mailbox subscriptions if they don't exist.
     *
     * @param Mailbox $mailbox
     * @return void
     */
    protected function createMailboxSubscriptions(Mailbox $mailbox): void
    {
        $mailbox->subscriptions->each->delete();

        Subscriptions::subscribe($mailbox);
    }

    /**
     * Get the command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['email', InputArgument::REQUIRED, 'The email address of the inbox.'],
        ];
    }

    /**
     * Get the command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['connection', 'c', InputOption::VALUE_OPTIONAL, 'The name of the connection to test.', 'default'],
        ];
    }
}