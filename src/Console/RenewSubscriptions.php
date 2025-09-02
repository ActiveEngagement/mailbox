<?php

namespace Actengage\Mailbox\Console;

use Actengage\Mailbox\Facades\Client;
use Actengage\Mailbox\Facades\Subscriptions;
use Actengage\Mailbox\Models\Mailbox;
use Illuminate\Console\Command;
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
    protected $command = 'mailbox:resubscribe';

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
        $mailboxes = $this->option('email')
            ? Mailbox::email($this->option('email'))->get()
            : Mailbox::all();

        foreach($mailboxes as $mailbox) {
            $hasSubscriptions = $mailbox->subscriptions()
                ->expiresAt(now()->addHour())
                ->exists();
                
            if(!$hasSubscriptions) {
                $this->warn("$mailbox->email has no subscriptions to renew!");

                continue;
            }
            
            $subscriptions = $mailbox->subscriptions;

            Client::connect($mailbox->connection);

            Subscriptions::subscribe($mailbox);
    
            $subscriptions->each->delete();

            $this->info("The subscriptions $mailbox->email have been resubscribed!");
        }

        return 0;
    }

    /**
     * Get the command arguments.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['email', 'e', InputOption::VALUE_OPTIONAL, 'The email address of the inbox.'],
        ];
    }
}
