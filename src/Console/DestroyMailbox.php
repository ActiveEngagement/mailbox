<?php

namespace Actengage\Mailbox\Console;

use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Console\Command;
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
    protected $command = 'mailbox:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Destory the mailbox for the given email address.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mailbox = Mailbox::email($this->argument('email'))->firstOrFail();
        $mailbox->subscriptions->each->delete();
        $mailbox->delete();

        $this->info("$mailbox->email was destroyed!");
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
}
